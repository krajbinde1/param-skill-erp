<?php

namespace App\Filament\Pages;

use App\Enums\EmployeeRole;
use App\Enums\RoleName;
use App\Enums\StudentAdmissionStatus;
use App\Models\Employee;
use App\Models\Student;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MobilizerPerformanceReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Mobilizer Performance';

    protected static ?string $title = 'Mobilizer Performance Report';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.mobilizer-performance-report';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isElevated() || $user?->hasRole(RoleName::CentreManager->value));
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Employee::query()
                    ->withoutGlobalScopes()
                    ->where('employee_role', EmployeeRole::Mobilizer)
                    ->when(
                        auth()->user()?->isCentreManager() && ! auth()->user()?->isElevated(),
                        fn (Builder $q) => $q->where('centre_id', auth()->user()->centre_id)
                    )
                    ->with('centre')
            )
            ->columns([
                TextColumn::make('employee_code')->label('Mobilizer Code')->searchable(),
                TextColumn::make('full_name')->label('Mobilizer Name')->searchable(['first_name', 'last_name']),
                TextColumn::make('centre.centre_name')->label('Centre'),
                TextColumn::make('total_students')
                    ->label('Total Students')
                    ->state(fn (Employee $record): int => Student::withoutGlobalScopes()->where('mobilizer_id', $record->id)->count()),
                TextColumn::make('today_students')
                    ->label('Today')
                    ->state(fn (Employee $record): int => Student::withoutGlobalScopes()->where('mobilizer_id', $record->id)->whereDate('created_at', today())->count()),
                TextColumn::make('submitted')
                    ->label('Submitted')
                    ->state(fn (Employee $record): int => Student::withoutGlobalScopes()->where('mobilizer_id', $record->id)->whereNotNull('submitted_at')->count()),
                TextColumn::make('confirmed')
                    ->label('Confirmed')
                    ->state(fn (Employee $record): int => Student::withoutGlobalScopes()->where('mobilizer_id', $record->id)->where('admission_status', StudentAdmissionStatus::AdmissionConfirmed)->count()),
                TextColumn::make('joined')
                    ->label('Joined')
                    ->state(fn (Employee $record): int => Student::withoutGlobalScopes()->where('mobilizer_id', $record->id)->where('admission_status', StudentAdmissionStatus::JoinedCentre)->count()),
                TextColumn::make('rejected')
                    ->label('Rejected')
                    ->state(fn (Employee $record): int => Student::withoutGlobalScopes()->where('mobilizer_id', $record->id)->where('admission_status', StudentAdmissionStatus::Rejected)->count()),
                TextColumn::make('conversion')
                    ->label('Conversion %')
                    ->state(function (Employee $record): string {
                        $submitted = Student::withoutGlobalScopes()->where('mobilizer_id', $record->id)->whereNotNull('submitted_at')->count();
                        if ($submitted === 0) {
                            return '0%';
                        }
                        $won = Student::withoutGlobalScopes()->where('mobilizer_id', $record->id)->whereIn('admission_status', [
                            StudentAdmissionStatus::AdmissionConfirmed,
                            StudentAdmissionStatus::JoinedCentre,
                        ])->count();

                        return round(($won / $submitted) * 100, 1).'%';
                    }),
                TextColumn::make('overdue')
                    ->label('Overdue Follow-ups')
                    ->state(fn (Employee $record): int => Student::withoutGlobalScopes()->where('mobilizer_id', $record->id)->overdueFollowUp()->count()),
            ])
            ->filters([
                SelectFilter::make('centre_id')
                    ->label('Centre')
                    ->relationship('centre', 'centre_name')
                    ->visible(fn () => auth()->user()?->isElevated() ?? false)
                    ->searchable(),
            ])
            ->paginated([10, 25, 50]);
    }
}
