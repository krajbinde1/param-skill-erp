<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class StudentsByDistrictChart extends ChartWidget
{
    protected ?string $heading = 'Students by District';

    protected static ?int $sort = 10;

    public static function canView(): bool
    {
        return auth()->user()?->isElevated() ?? false;
    }

    protected function getData(): array
    {
        $rows = Student::query()
            ->select('district', DB::raw('count(*) as aggregate'))
            ->groupBy('district')
            ->orderByDesc('aggregate')
            ->limit(10)
            ->get();

        return [
            'datasets' => [['label' => 'Students', 'data' => $rows->pluck('aggregate')->all()]],
            'labels' => $rows->pluck('district')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
