<?php

namespace App\Filament\Resources\Centres;

use App\Filament\Resources\Centres\Pages\CreateCentre;
use App\Filament\Resources\Centres\Pages\EditCentre;
use App\Filament\Resources\Centres\Pages\ListCentres;
use App\Filament\Resources\Centres\Pages\ViewCentre;
use App\Filament\Resources\Centres\Schemas\CentreForm;
use App\Filament\Resources\Centres\Schemas\CentreInfolist;
use App\Filament\Resources\Centres\Tables\CentresTable;
use App\Models\Centre;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CentreResource extends Resource
{
    protected static ?string $model = Centre::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Centres';

    protected static string|UnitEnum|null $navigationGroup = 'Centre Management';

    protected static ?string $modelLabel = 'Centre';

    protected static ?string $pluralModelLabel = 'Centres';

    protected static ?string $recordTitleAttribute = 'centre_name';

    protected static ?string $slug = null;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return CentreForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CentreInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CentresTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCentres::route('/'),
            'create' => CreateCentre::route('/create'),
            'view' => ViewCentre::route('/{record}'),
            'edit' => EditCentre::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isElevated();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withCount('employees');

        $user = auth()->user();

        if ($user instanceof User && $user->can('centres.restore')) {
            $query->withTrashed();
        }

        return $query;
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return "{$record->centre_code} — {$record->centre_name}";
    }
}
