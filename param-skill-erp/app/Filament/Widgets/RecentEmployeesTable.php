<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Support\Format;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentEmployeesTable extends TableWidget
{
    protected static ?string $heading = 'Recently Added Employees';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isElevated() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Employee::withoutGlobalScopes()->with('centre')->latest()->limit(5))
            ->columns([
                TextColumn::make('employee_code')->label('Code'),
                TextColumn::make('full_name')->label('Name'),
                TextColumn::make('centre.centre_name')->label('Centre'),
                TextColumn::make('employee_role')->label('Role'),
                TextColumn::make('created_at')->label('Created')->formatStateUsing(fn ($state) => Format::date($state)),
            ])
            ->paginated(false);
    }
}
