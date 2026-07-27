<?php

namespace App\Filament\Resources\Students\Pages;

use App\Enums\StudentAdmissionStatus;
use App\Filament\Resources\Students\StudentResource;
use App\Services\StudentWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('changeAdmissionStatus')
                ->label('Update Admission Status')
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->color('warning')
                ->authorize('changeAdmissionStatus')
                ->schema(fn () => [
                    Select::make('admission_status')
                        ->label('New Status')
                        ->options(StudentAdmissionStatus::options())
                        ->default($this->getRecord()->admission_status->value)
                        ->native(false)
                        ->required(),
                    Textarea::make('remark')
                        ->label('Remark')
                        ->rows(2),
                    Textarea::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->helperText('Required when moving to Rejected or Not Interested.')
                        ->rows(2),
                ])
                ->action(function (array $data, StudentWorkflowService $service): void {
                    $service->transitionAdmission(
                        $this->getRecord(),
                        StudentAdmissionStatus::from($data['admission_status']),
                        auth()->user(),
                        $data['remark'] ?? null,
                        $data['rejection_reason'] ?? null,
                    );

                    $this->getRecord()->refresh();
                })
                ->successNotificationTitle('Admission status updated'),
        ];
    }
}
