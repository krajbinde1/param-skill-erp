<?php

namespace App\Filament\Widgets;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use Filament\Widgets\ChartWidget;

class EmployeeStatusChart extends ChartWidget
{
    protected ?string $heading = 'Active vs Inactive Employees';

    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        return auth()->user()?->isElevated() ?? false;
    }

    protected function getData(): array
    {
        $active = Employee::withoutGlobalScopes()->where('status', EmployeeStatus::Active)->count();
        $inactive = Employee::withoutGlobalScopes()->where('status', EmployeeStatus::Inactive)->count();

        return [
            'datasets' => [
                [
                    'data' => [$active, $inactive],
                    'backgroundColor' => ['#0d9488', '#94a3b8'],
                ],
            ],
            'labels' => ['Active', 'Inactive'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
