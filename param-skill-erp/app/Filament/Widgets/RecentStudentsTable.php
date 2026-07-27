<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use App\Support\Format;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentStudentsTable extends TableWidget
{
    protected static ?string $heading = 'Recently Added Students';

    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isElevated() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Student::query()->with(['centre', 'mobilizer'])->latest()->limit(8))
            ->columns([
                TextColumn::make('student_code')->label('Code'),
                TextColumn::make('full_name')->label('Name'),
                TextColumn::make('district'),
                TextColumn::make('admission_status')->badge(),
                TextColumn::make('created_at')->formatStateUsing(fn ($state) => Format::date($state)),
            ])
            ->paginated(false);
    }
}
