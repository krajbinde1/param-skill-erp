<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EmployeesByRoleChart extends ChartWidget
{
    protected ?string $heading = 'Employees by Role';

    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()?->isElevated() ?? false;
    }

    protected function getData(): array
    {
        $rows = Employee::withoutGlobalScopes()
            ->select('employee_role', DB::raw('count(*) as aggregate'))
            ->groupBy('employee_role')
            ->orderByDesc('aggregate')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Employees',
                    'data' => $rows->pluck('aggregate')->all(),
                ],
            ],
            'labels' => $rows->pluck('employee_role')->map(fn ($role) => is_object($role) ? $role->value : $role)->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
