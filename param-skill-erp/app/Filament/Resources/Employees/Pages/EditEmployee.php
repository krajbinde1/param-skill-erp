<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Enums\EmployeeRole;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Services\CentreContext;
use App\Services\EmployeeManagementService;
use App\Support\SensitiveData;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected ?EmployeeRole $previousRole = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['aadhaar_number'] = null;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $context = app(CentreContext::class);

        if ($context->isCentreManager() && ! $context->isElevated()) {
            $data['centre_id'] = $this->getRecord()->centre_id;
        }

        $service = app(EmployeeManagementService::class);

        if (filled($data['mobile'] ?? null)) {
            $service->assertUniqueMobile($data['mobile'], $this->getRecord()->id);
            $data['mobile'] = SensitiveData::normalizeIndianMobile($data['mobile']);
        }

        if (filled($data['aadhaar_number'] ?? null)) {
            $service->assertUniqueAadhaar($data['aadhaar_number'], $this->getRecord()->id);
        }

        $this->previousRole = $this->getRecord()->employee_role;
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function afterSave(): void
    {
        $employee = $this->getRecord()->fresh();
        $data = $this->form->getState();

        if (array_key_exists('aadhaar_number', $data) && filled($data['aadhaar_number'])) {
            $employee->setAadhaar($data['aadhaar_number']);
            $employee->save();
        }

        app(EmployeeManagementService::class)->syncLinkedUser($employee, $this->previousRole);
    }
}
