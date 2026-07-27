<?php

namespace App\Filament\Widgets;

use App\Models\Centre;
use App\Support\Format;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentCentresTable extends TableWidget
{
    protected static ?string $heading = 'Recently Added Centres';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isElevated() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Centre::query()->latest()->limit(5))
            ->columns([
                TextColumn::make('centre_code')->label('Code'),
                TextColumn::make('centre_name')->label('Name')->limit(30),
                TextColumn::make('district'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->label('Created')->formatStateUsing(fn ($state) => Format::date($state)),
            ])
            ->paginated(false);
    }
}
