<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

/**
 * Replaced by role-specific dashboards in Phase 2.
 */
class FoundationOverviewWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.foundation-overview';

    public static function canView(): bool
    {
        return false;
    }
}
