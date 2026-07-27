<?php

namespace App\Filament\Widgets;

use App\Models\Centre;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CentresByDistrictChart extends ChartWidget
{
    protected ?string $heading = 'Centres by District';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->isElevated() ?? false;
    }

    protected function getData(): array
    {
        $rows = Centre::query()
            ->select('district', DB::raw('count(*) as aggregate'))
            ->groupBy('district')
            ->orderByDesc('aggregate')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Centres',
                    'data' => $rows->pluck('aggregate')->all(),
                ],
            ],
            'labels' => $rows->pluck('district')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
