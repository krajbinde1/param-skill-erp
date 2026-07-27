<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AdmissionStatusChart extends ChartWidget
{
    protected ?string $heading = 'Admission Status Distribution';

    protected static ?int $sort = 11;

    public static function canView(): bool
    {
        return auth()->user()?->isElevated() ?? false;
    }

    protected function getData(): array
    {
        $rows = Student::query()
            ->select('admission_status', DB::raw('count(*) as aggregate'))
            ->groupBy('admission_status')
            ->orderByDesc('aggregate')
            ->get();

        return [
            'datasets' => [[
                'data' => $rows->pluck('aggregate')->all(),
                'backgroundColor' => ['#0d9488', '#f59e0b', '#3b82f6', '#22c55e', '#ef4444', '#94a3b8', '#8b5cf6', '#06b6d4', '#84cc16', '#f97316', '#64748b', '#a855f7', '#14b8a6'],
            ]],
            'labels' => $rows->pluck('admission_status')->map(fn ($s) => is_object($s) ? $s->value : $s)->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
