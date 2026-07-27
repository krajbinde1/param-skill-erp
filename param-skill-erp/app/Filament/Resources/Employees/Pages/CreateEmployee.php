<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Services\CentreContext;
use App\Services\EmployeeManagementService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected ?string $temporaryPassword = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $context = app(CentreContext::class);

        if ($context->isCentreManager() && ! $context->isElevated()) {
            $data['centre_id'] = $context->centreId();
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $result = app(EmployeeManagementService::class)->create($data, auth()->user());
        $this->temporaryPassword = $result['temporary_password'];

        return $result['employee'];
    }

    protected function getCreatedNotification(): ?Notification
    {
        $employee = $this->getRecord();

        return Notification::make()
            ->success()
            ->title('Employee created')
            ->body("Login ID: {$employee->employee_code}\nTemporary password: {$this->temporaryPassword}\nShare securely. Shown only once.")
            ->persistent();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
