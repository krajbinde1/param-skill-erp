<?php

namespace App\Filament\Resources\Centres\Tables;

use App\Enums\CentreStatus;
use App\Models\Centre;
use App\Services\CentreManagementService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class CentresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('centre_code')
                    ->label('Code')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('centre_name')
                    ->label('Centre Name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('manager_name')
                    ->label('Manager')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('manager_mobile')
                    ->label('Mobile')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('district')
                    ->label('District')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('centre_capacity')
                    ->label('Capacity')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),
                IconColumn::make('hostel_available')
                    ->label('Hostel')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('employees_count')
                    ->label('Employees')
                    ->counts('employees')
                    ->numeric()
                    ->alignCenter()
                    ->badge()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (CentreStatus $state) => $state === CentreStatus::Active ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->date('d-m-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(CentreStatus::cases())->mapWithKeys(
                        fn (CentreStatus $status) => [$status->value => $status->label()]
                    )),
                SelectFilter::make('district')
                    ->options(fn () => Centre::query()
                        ->whereNotNull('district')
                        ->distinct()
                        ->orderBy('district')
                        ->pluck('district', 'district')
                        ->all())
                    ->searchable(),
                SelectFilter::make('state')
                    ->options(fn () => Centre::query()
                        ->whereNotNull('state')
                        ->distinct()
                        ->orderBy('state')
                        ->pluck('state', 'state')
                        ->all())
                    ->searchable(),
                TernaryFilter::make('hostel_available')
                    ->label('Hostel Available'),
                Filter::make('created_at')
                    ->label('Created Date Range')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('Created From')
                            ->native(false),
                        DatePicker::make('created_until')
                            ->label('Created Until')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'Created from '.$data['created_from'];
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Created until '.$data['created_until'];
                        }

                        return $indicators;
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('activate')
                        ->label('Activate')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->authorize('activate')
                        ->visible(fn (Centre $record) => $record->status === CentreStatus::Inactive)
                        ->requiresConfirmation()
                        ->modalDescription('This will activate the centre and re-enable the manager login.')
                        ->action(function (Centre $record, CentreManagementService $service) {
                            $service->setStatus($record, CentreStatus::Active, auth()->user());
                        })
                        ->successNotificationTitle('Centre activated'),
                    Action::make('deactivate')
                        ->label('Deactivate')
                        ->icon(Heroicon::OutlinedNoSymbol)
                        ->color('danger')
                        ->authorize('deactivate')
                        ->visible(fn (Centre $record) => $record->status === CentreStatus::Active)
                        ->requiresConfirmation()
                        ->modalDescription('This will deactivate the centre and block the manager login.')
                        ->action(function (Centre $record, CentreManagementService $service) {
                            $service->setStatus($record, CentreStatus::Inactive, auth()->user());
                        })
                        ->successNotificationTitle('Centre deactivated'),
                    Action::make('resetManagerLogin')
                        ->label('Reset Manager Login')
                        ->icon(Heroicon::OutlinedKey)
                        ->color('warning')
                        ->authorize('resetLogin')
                        ->requiresConfirmation()
                        ->modalHeading('Reset Manager Login')
                        ->modalDescription('This will generate a new temporary password for the centre manager. The current password will stop working immediately.')
                        ->modalSubmitActionLabel('Reset Login')
                        ->action(function (Centre $record, CentreManagementService $service, Action $action) {
                            $result = $service->resetManagerLogin($record, auth()->user());

                            $action->getLivewire()->replaceMountedAction('showManagerCredentials', [
                                'loginId' => $result['user']->login_id,
                                'password' => $result['temporary_password'],
                            ]);
                        }),
                    Action::make('showManagerCredentials')
                        ->label('Manager Login Credentials')
                        ->hidden()
                        ->modalHeading('Manager Login Reset')
                        ->modalDescription('Copy these credentials now. The temporary password will not be shown again.')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->schema(fn (array $arguments) => [
                            TextInput::make('login_id')
                                ->label('Login ID')
                                ->default($arguments['loginId'] ?? null)
                                ->readOnly()
                                ->copyable(),
                            TextInput::make('password')
                                ->label('Temporary Password')
                                ->default($arguments['password'] ?? null)
                                ->readOnly()
                                ->copyable(),
                        ]),
                    Action::make('downloadAgreement')
                        ->label('Download Agreement')
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->authorize('view')
                        ->visible(fn (Centre $record) => filled($record->agreement_document))
                        ->action(fn (Centre $record) => Storage::disk('private')->download(
                            $record->agreement_document,
                            "{$record->centre_code}-agreement.".pathinfo($record->agreement_document, PATHINFO_EXTENSION)
                        )),
                    DeleteAction::make(),
                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Activate')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->authorize('activate')
                        ->requiresConfirmation()
                        ->action(function (Collection $records, CentreManagementService $service) {
                            $actor = auth()->user();

                            $records->each(fn (Centre $centre) => $service->setStatus($centre, CentreStatus::Active, $actor));
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon(Heroicon::OutlinedNoSymbol)
                        ->color('danger')
                        ->authorize('deactivate')
                        ->requiresConfirmation()
                        ->action(function (Collection $records, CentreManagementService $service) {
                            $actor = auth()->user();

                            $records->each(fn (Centre $centre) => $service->setStatus($centre, CentreStatus::Inactive, $actor));
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
