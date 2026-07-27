<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Services\CentreContext;
use App\Services\StudentWorkflowService;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

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
        unset($data['student_code']);

        $context = app(CentreContext::class);

        if ($context->isCentreManager() && ! $context->isElevated()) {
            $data['centre_id'] = $this->getRecord()->centre_id;
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(StudentWorkflowService::class)->update($record, $data, auth()->user());
    }
}
