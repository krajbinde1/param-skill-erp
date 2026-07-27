<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Services\CentreContext;
use App\Services\StudentWorkflowService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

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
        return app(StudentWorkflowService::class)->create($data, auth()->user());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
