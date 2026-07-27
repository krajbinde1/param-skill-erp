<?php

namespace App\Filament\Resources\Students\Tables;

use App\Enums\CentreStatus;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Enums\StudentAdmissionStatus;
use App\Enums\StudentCentreVisitStatus;
use App\Enums\StudentVerificationStatus;
use App\Enums\StudentVisitRecordStatus;
use App\Models\Centre;
use App\Models\Employee;
use App\Models\Student;
use App\Services\StudentWorkflowService;
use App\Support\Format;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student_code')
                    ->label('Code')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('full_name')
                    ->label('Student Name')
                    ->state(fn (Student $record) => $record->full_name)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $query) use ($search): void {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
                    ->wrap(),
                TextColumn::make('mobile')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $query) use ($search): void {
                            $query->where('mobile', 'like', "%{$search}%")
                                ->orWhere('parent_mobile', 'like', "%{$search}%");
                        });
                    })
                    ->copyable(),
                TextColumn::make('district')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('taluka')
                    ->searchable(['taluka', 'village'])
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('mobilizer.full_name')
                    ->label('Mobilizer')
                    ->searchable(['mobilizer.first_name', 'mobilizer.middle_name', 'mobilizer.last_name'])
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('centre.centre_name')
                    ->label('Centre')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('preferredCentre.centre_name')
                    ->label('Preferred Centre')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
                TextColumn::make('admission_status')
                    ->label('Admission Status')
                    ->badge()
                    ->color(fn (StudentAdmissionStatus $state) => $state->color())
                    ->sortable(),
                TextColumn::make('verification_status')
                    ->label('Verification')
                    ->badge()
                    ->color(fn (StudentVerificationStatus $state) => $state->color())
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('centre_visit_status')
                    ->label('Centre Visit')
                    ->badge()
                    ->color(fn (StudentCentreVisitStatus $state) => $state->color())
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('next_follow_up_date')
                    ->label('Next Follow-up')
                    ->formatStateUsing(fn ($state) => Format::date($state))
                    ->color(function (Student $record) {
                        if (blank($record->next_follow_up_date) || $record->admission_status->isFinal()) {
                            return null;
                        }

                        return $record->next_follow_up_date->isPast() ? 'danger' : null;
                    })
                    ->weight(fn (Student $record) => (! blank($record->next_follow_up_date) && ! $record->admission_status->isFinal() && $record->next_follow_up_date->isPast()) ? 'bold' : null)
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->formatStateUsing(fn ($state) => Format::date($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('centre_id')
                    ->label('Centre')
                    ->relationship('centre', 'centre_name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('preferred_centre_id')
                    ->label('Preferred Centre')
                    ->relationship('preferredCentre', 'centre_name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('mobilizer_id')
                    ->label('Mobilizer')
                    ->options(fn () => Employee::query()
                        ->where('employee_role', EmployeeRole::Mobilizer)
                        ->orderBy('first_name')
                        ->get()
                        ->mapWithKeys(fn (Employee $employee) => [$employee->id => $employee->full_name]))
                    ->searchable(),
                SelectFilter::make('district')
                    ->options(fn () => Student::query()
                        ->whereNotNull('district')
                        ->distinct()
                        ->orderBy('district')
                        ->pluck('district', 'district')
                        ->all())
                    ->searchable(),
                SelectFilter::make('taluka')
                    ->options(fn () => Student::query()
                        ->whereNotNull('taluka')
                        ->distinct()
                        ->orderBy('taluka')
                        ->pluck('taluka', 'taluka')
                        ->all())
                    ->searchable(),
                SelectFilter::make('admission_status')
                    ->label('Admission Status')
                    ->options(StudentAdmissionStatus::options()),
                SelectFilter::make('verification_status')
                    ->label('Verification Status')
                    ->options(StudentVerificationStatus::options()),
                SelectFilter::make('centre_visit_status')
                    ->label('Centre Visit Status')
                    ->options(StudentCentreVisitStatus::options()),
                TernaryFilter::make('hostel_required')
                    ->label('Hostel Required'),
                Filter::make('created_at')
                    ->label('Created Date')
                    ->schema([
                        DatePicker::make('from')->displayFormat('d-m-Y')->native(false),
                        DatePicker::make('until')->displayFormat('d-m-Y')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
                Filter::make('overdue_follow_up')
                    ->label('Overdue Follow-up')
                    ->query(fn (Builder $query): Builder => $query->overdueFollowUp())
                    ->toggle(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('changeAdmissionStatus')
                        ->label('Update Admission Status')
                        ->icon(Heroicon::OutlinedArrowsRightLeft)
                        ->color('warning')
                        ->authorize('changeAdmissionStatus')
                        ->visible(fn (Student $record) => ! $record->trashed())
                        ->schema(fn (Student $record) => [
                            Select::make('admission_status')
                                ->label('New Status')
                                ->options(StudentAdmissionStatus::options())
                                ->default($record->admission_status->value)
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
                        ->action(function (Student $record, array $data, StudentWorkflowService $service): void {
                            $service->transitionAdmission(
                                $record,
                                StudentAdmissionStatus::from($data['admission_status']),
                                auth()->user(),
                                $data['remark'] ?? null,
                                $data['rejection_reason'] ?? null,
                            );
                        })
                        ->successNotificationTitle('Admission status updated'),
                    Action::make('confirmCentreVisit')
                        ->label('Confirm Centre Visit')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->authorize('confirmCentreVisit')
                        ->visible(fn (Student $record) => ! $record->trashed() && $record->centreVisits()
                            ->where('visit_status', StudentVisitRecordStatus::CentreVisited)
                            ->exists())
                        ->schema([
                            Textarea::make('manager_remark')
                                ->label('Remark')
                                ->rows(2),
                        ])
                        ->action(function (Student $record, array $data, StudentWorkflowService $service): void {
                            $visit = $record->centreVisits()
                                ->where('visit_status', StudentVisitRecordStatus::CentreVisited)
                                ->latest('visit_date')
                                ->first();

                            if ($visit) {
                                $service->confirmCentreVisit($visit, auth()->user(), $data['manager_remark'] ?? null);
                            }
                        })
                        ->successNotificationTitle('Centre visit confirmed'),
                    Action::make('reassign')
                        ->label('Reassign')
                        ->icon(Heroicon::OutlinedArrowPath)
                        ->color('gray')
                        ->authorize('reassign')
                        ->visible(fn (Student $record) => ! $record->trashed())
                        ->schema(fn (Student $record) => [
                            Select::make('centre_id')
                                ->label('Centre')
                                ->live()
                                ->searchable()
                                ->preload()
                                ->default($record->centre_id)
                                ->options(fn () => Centre::query()
                                    ->where('status', CentreStatus::Active)
                                    ->orderBy('centre_name')
                                    ->pluck('centre_name', 'id')),
                            Select::make('mobilizer_id')
                                ->label('Mobilizer')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->default($record->mobilizer_id)
                                ->options(function (Get $get) {
                                    $centreId = $get('centre_id');

                                    return Employee::query()
                                        ->where('employee_role', EmployeeRole::Mobilizer)
                                        ->where('status', EmployeeStatus::Active)
                                        ->when($centreId, fn ($query) => $query->where('centre_id', $centreId))
                                        ->orderBy('first_name')
                                        ->get()
                                        ->mapWithKeys(fn (Employee $employee) => [
                                            $employee->id => "{$employee->employee_code} — {$employee->full_name}",
                                        ]);
                                }),
                        ])
                        ->action(function (Student $record, array $data, StudentWorkflowService $service): void {
                            $service->reassign($record, $data, auth()->user());
                        })
                        ->successNotificationTitle('Student reassigned'),
                    Action::make('managerRemark')
                        ->label('Manager Remark')
                        ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                        ->color('gray')
                        ->visible(fn (Student $record) => ! $record->trashed() && auth()->user()?->can('update', $record))
                        ->schema(fn (Student $record) => [
                            Textarea::make('remark')
                                ->label('Remark')
                                ->default($record->remark)
                                ->rows(3),
                        ])
                        ->action(function (Student $record, array $data): void {
                            $record->update([
                                'remark' => $data['remark'] ?? null,
                                'updated_by' => auth()->id(),
                            ]);
                        })
                        ->successNotificationTitle('Remark updated'),
                    DeleteAction::make(),
                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
