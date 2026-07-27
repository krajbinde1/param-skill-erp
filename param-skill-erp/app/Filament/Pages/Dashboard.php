<?php

namespace App\Filament\Pages;

use App\Enums\RoleName;
use App\Filament\Widgets\AdminStatsOverview;
use App\Filament\Widgets\AdminStudentStatsOverview;
use App\Filament\Widgets\AdmissionStatusChart;
use App\Filament\Widgets\CentreManagerEmployeeStats;
use App\Filament\Widgets\CentreManagerOverview;
use App\Filament\Widgets\CentreManagerRecentEmployees;
use App\Filament\Widgets\CentreManagerStudentStats;
use App\Filament\Widgets\CentresByDistrictChart;
use App\Filament\Widgets\CentreStatusChart;
use App\Filament\Widgets\EmployeesByRoleChart;
use App\Filament\Widgets\EmployeeStatusChart;
use App\Filament\Widgets\RecentCentresTable;
use App\Filament\Widgets\RecentEmployeesTable;
use App\Filament\Widgets\RecentStudentsTable;
use App\Filament\Widgets\StudentsByDistrictChart;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole([
            RoleName::SuperAdmin->value,
            RoleName::Admin->value,
            RoleName::CentreManager->value,
        ]);
    }

    public function getWidgets(): array
    {
        $user = auth()->user();

        if ($user?->isElevated()) {
            return [
                AdminStatsOverview::class,
                AdminStudentStatsOverview::class,
                CentresByDistrictChart::class,
                EmployeesByRoleChart::class,
                CentreStatusChart::class,
                EmployeeStatusChart::class,
                StudentsByDistrictChart::class,
                AdmissionStatusChart::class,
                RecentCentresTable::class,
                RecentEmployeesTable::class,
                RecentStudentsTable::class,
            ];
        }

        return [
            CentreManagerOverview::class,
            CentreManagerEmployeeStats::class,
            CentreManagerStudentStats::class,
            CentreManagerRecentEmployees::class,
        ];
    }
}
