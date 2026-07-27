<?php

namespace App\Filament\Widgets;

use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Enums\RoleName;
use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CentreManagerEmployeeStats extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return (auth()->user()?->hasRole(RoleName::CentreManager->value) && auth()->user()?->centre_id) ?? false;
    }

    protected function getStats(): array
    {
        $query = Employee::query();

        return [
            Stat::make('Total Employees', (clone $query)->count()),
            Stat::make('Active Employees', (clone $query)->where('status', EmployeeStatus::Active)->count()),
            Stat::make('Mobilizers', (clone $query)->where('employee_role', EmployeeRole::Mobilizer)->count()),
            Stat::make('Trainers', (clone $query)->where('employee_role', EmployeeRole::Trainer)->count()),
            Stat::make('Wardens', (clone $query)->where('employee_role', EmployeeRole::Warden)->count()),
            Stat::make('Watchmen', (clone $query)->where('employee_role', EmployeeRole::Watchman)->count()),
            Stat::make('Housekeepers', (clone $query)->where('employee_role', EmployeeRole::Housekeeper)->count()),
        ];
    }
}
