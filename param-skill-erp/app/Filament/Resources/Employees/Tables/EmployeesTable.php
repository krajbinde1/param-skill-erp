<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Centre;
use App\Models\Employee;
use App\Services\CentreContext;
use App\Services\EmployeeManagementService;
use App\Support\Format;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        $elevated = app(CentreContext::class)->isElevated();

        return $table
            ->columns([
                TextColumn::make('employee_code')->label('Employee Code')->searchable()->sortable(),
                TextColumn::make('full_name')->label('Full Name')->searchable(['first_name', 'middle_name', 'last_name']),
                TextColumn::make('centre.centre_name')->label('Centre')->visible($elevated)->searchable(),
                TextColumn::make('mobile')->searchable(),
                TextColumn::make('employee_role')->label('Role')->badge(),
                TextColumn::make('district')->searchable(),
                TextColumn::make('joining_date')->formatStateUsing(fn ($state) => Format::date($state))->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('created_at')->label('Created')->formatStateUsing(fn ($state) => Format::date($state))->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('centre_id')
                    ->label('Centre')
                    ->visible($elevated)
                    ->options(fn () => Centre::query()->orderBy('centre_name')->pluck('centre_name', 'id'))
                    ->searchable(),
                SelectFilter::make('employee_role')->label('Role')->options(EmployeeRole::options()),
                SelectFilter::make('status')->options([
                    EmployeeStatus::Active->value => 'Active',
                    EmployeeStatus::Inactive->value => 'Inactive',
                ]),
                SelectFilter::make('district')->options(fn () => Employee::query()->whereNotNull('district')->distinct()->orderBy('district')->pluck('district', 'district')->all()),
                Filter::make('joining_date')
                    ->form([
                        DatePicker::make('from')->displayFormat('d-m-Y')->native(false),
                        DatePicker::make('until')->displayFormat('d-m-Y')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('joining_date', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('joining_date', '<=', $date));
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Employee $record) => $record->status === EmployeeStatus::Inactive && auth()->user()?->can('activate', $record))
                    ->requiresConfirmation()
                    ->action(function (Employee $record): void {
                        app(EmployeeManagementService::class)->setStatus($record, EmployeeStatus::Active, auth()->user());
                        Notification::make()->title('Employee activated')->success()->send();
                    }),
                Action::make('deactivate')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->visible(fn (Employee $record) => $record->status === EmployeeStatus::Active && auth()->user()?->can('deactivate', $record))
                    ->requiresConfirmation()
                    ->action(function (Employee $record): void {
                        app(EmployeeManagementService::class)->setStatus($record, EmployeeStatus::Inactive, auth()->user());
                        Notification::make()->title('Employee deactivated')->success()->send();
                    }),
                Action::make('resetLogin')
                    ->label('Reset Login')
                    ->icon('heroicon-o-key')
                    ->color('danger')
                    ->visible(fn (Employee $record) => auth()->user()?->can('resetLogin', $record))
                    ->requiresConfirmation()
                    ->action(function (Employee $record): void {
                        $result = app(EmployeeManagementService::class)->resetLogin($record, auth()->user());
                        Notification::make()
                            ->title('Employee login reset')
                            ->body("Login ID: {$result['user']->login_id}\nTemporary password: {$result['temporary_password']}\nShown only once.")
                            ->success()
                            ->persistent()
                            ->send();
                    }),
                Action::make('downloadAadhaar')
                    ->label('Aadhaar Doc')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (Employee $record) => filled($record->aadhaar_document) && auth()->user()?->can('downloadDocuments', $record))
                    ->action(fn (Employee $record) => Storage::disk('private')->download($record->aadhaar_document)),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $service = app(EmployeeManagementService::class);
                            $records->each(function (Employee $employee) use ($service): void {
                                if (auth()->user()?->can('activate', $employee)) {
                                    $service->setStatus($employee, EmployeeStatus::Active, auth()->user());
                                }
                            });
                        }),
                    BulkAction::make('deactivate')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $service = app(EmployeeManagementService::class);
                            $records->each(function (Employee $employee) use ($service): void {
                                if (auth()->user()?->can('deactivate', $employee)) {
                                    $service->setStatus($employee, EmployeeStatus::Inactive, auth()->user());
                                }
                            });
                        }),
                ]),
            ]);
    }
}
