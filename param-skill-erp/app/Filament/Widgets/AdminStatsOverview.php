<?php

namespace App\Filament\Widgets;

use App\Enums\CentreStatus;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Enums\RoleName;
use App\Models\Centre;
use App\Models\Employee;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->isElevated() ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Centres', Centre::query()->count()),
            Stat::make('Active Centres', Centre::query()->where('status', CentreStatus::Active)->count()),
            Stat::make('Inactive Centres', Centre::query()->where('status', CentreStatus::Inactive)->count()),
            Stat::make('Total Employees', Employee::withoutGlobalScopes()->count()),
            Stat::make('Active Employees', Employee::withoutGlobalScopes()->where('status', EmployeeStatus::Active)->count()),
            Stat::make('Centre Managers', User::role(RoleName::CentreManager->value)->count()),
            Stat::make('Mobilizers', Employee::withoutGlobalScopes()->where('employee_role', EmployeeRole::Mobilizer)->count()),
            Stat::make('Trainers', Employee::withoutGlobalScopes()->where('employee_role', EmployeeRole::Trainer)->count()),
        ];
    }
}
