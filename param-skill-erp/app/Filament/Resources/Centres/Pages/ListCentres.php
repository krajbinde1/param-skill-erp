<?php

namespace App\Filament\Resources\Centres\Pages;

use App\Filament\Resources\Centres\CentreResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCentres extends ListRecords
{
    protected static string $resource = CentreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(route('exports.centres.csv'))
                ->openUrlInNewTab(),
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(route('exports.centres.pdf'))
                ->openUrlInNewTab(),
            CreateAction::make(),
        ];
    }
}
