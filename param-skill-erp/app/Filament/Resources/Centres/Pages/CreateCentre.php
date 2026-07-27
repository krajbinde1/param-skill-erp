<?php

namespace App\Filament\Resources\Centres\Pages;

use App\Filament\Resources\Centres\CentreResource;
use App\Services\CentreManagementService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCentre extends CreateRecord
{
    protected static string $resource = CentreResource::class;

    protected ?string $temporaryPassword = null;

    protected function handleRecordCreation(array $data): Model
    {
        $result = app(CentreManagementService::class)->create($data, auth()->user());
        $this->temporaryPassword = $result['temporary_password'];

        return $result['centre'];
    }

    protected function getCreatedNotification(): ?Notification
    {
        $centre = $this->getRecord();

        return Notification::make()
            ->success()
            ->title('Centre created')
            ->body("Centre Code / Login ID: {$centre->centre_code}\nTemporary password: {$this->temporaryPassword}\nShare securely. Shown only once.")
            ->persistent();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
