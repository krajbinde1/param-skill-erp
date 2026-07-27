<?php

namespace App\Filament\Widgets;

use App\Enums\CentreStatus;
use App\Models\Centre;
use Filament\Widgets\ChartWidget;

class CentreStatusChart extends ChartWidget
{
    protected ?string $heading = 'Active vs Inactive Centres';

    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return auth()->user()?->isElevated() ?? false;
    }

    protected function getData(): array
    {
        $active = Centre::query()->where('status', CentreStatus::Active)->count();
        $inactive = Centre::query()->where('status', CentreStatus::Inactive)->count();

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
