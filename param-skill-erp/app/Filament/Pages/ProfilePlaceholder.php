<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ProfilePlaceholder extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Profile';

    protected static ?string $title = 'Profile';

    protected static string|\UnitEnum|null $navigationGroup = 'Account';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.profile-placeholder';
}
