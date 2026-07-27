<?php

namespace App\Filament\Widgets;

use App\Enums\RoleName;
use App\Support\Format;
use Filament\Widgets\Widget;

class CentreManagerOverview extends Widget
{
    protected static ?int $sort = 1;

    protected string $view = 'filament.widgets.centre-manager-overview';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(RoleName::CentreManager->value) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $centre = auth()->user()?->centre;

        if ($centre === null) {
            return [
                'missingCentre' => true,
                'centre' => null,
            ];
        }

        return [
            'missingCentre' => false,
            'centre' => $centre,
            'openingDate' => Format::date($centre->opening_date),
        ];
    }
}
