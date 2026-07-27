<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class FoundationOverviewWidget extends Widget
{
    protected static ?int $sort = 1;

    protected string $view = 'filament.widgets.foundation-overview';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = Auth::user();

        return [
            'appName' => config('app.name'),
            'userName' => $user?->name ?? 'Guest',
            'userRole' => $user?->primaryRoleName() ?? 'No role assigned',
            'showEnvironmentWarning' => ! app()->environment('production'),
            'environment' => config('app.env'),
        ];
    }
}
