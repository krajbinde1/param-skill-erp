<?php

namespace App\Filament\Widgets;

use App\Enums\RoleName;
use App\Models\Employee;
use App\Support\Format;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class CentreManagerRecentEmployees extends TableWidget
{
    protected static ?string $heading = 'Recently Added Employees';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (auth()->user()?->hasRole(RoleName::CentreManager->value) && auth()->user()?->centre_id) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Employee::query()->latest()->limit(8))
            ->columns([
                TextColumn::make('employee_code')->label('Code'),
                TextColumn::make('full_name')->label('Name'),
                TextColumn::make('employee_role')->label('Role'),
                TextColumn::make('mobile'),
                TextColumn::make('created_at')->formatStateUsing(fn ($state) => Format::date($state)),
            ])
            ->paginated(false);
    }
}
