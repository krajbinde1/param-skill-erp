<?php

namespace App\Filament\Resources\Centres\Schemas;

use App\Enums\CentreStatus;
use App\Models\Centre;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CentreInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Centre Information')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('centre_code')
                            ->label('Centre Code')
                            ->badge()
                            ->copyable(),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (CentreStatus $state) => $state === CentreStatus::Active ? 'success' : 'danger'),
                        TextEntry::make('employees_count')
                            ->label('Employees')
                            ->state(fn (Centre $record) => $record->employees_count ?? $record->employees()->count())
                            ->badge(),
                        TextEntry::make('centre_name')
                            ->label('Centre Name'),
                        TextEntry::make('scheme_name')
                            ->label('Scheme Name')
                            ->placeholder('—'),
                        TextEntry::make('project_name')
                            ->label('Project Name')
                            ->placeholder('—'),
                        TextEntry::make('manager_name')
                            ->label('Manager Name'),
                        TextEntry::make('manager_mobile')
                            ->label('Manager Mobile')
                            ->icon(Heroicon::OutlinedPhone)
                            ->copyable(),
                        TextEntry::make('manager_email')
                            ->label('Manager Email')
                            ->icon(Heroicon::OutlinedEnvelope)
                            ->placeholder('—'),
                    ]),

                Section::make('Capacity')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('centre_capacity')
                            ->label('Total Capacity')
                            ->placeholder('—'),
                        TextEntry::make('boys_capacity')
                            ->label('Boys Capacity')
                            ->placeholder('—'),
                        TextEntry::make('girls_capacity')
                            ->label('Girls Capacity')
                            ->placeholder('—'),
                        TextEntry::make('hostel_available')
                            ->label('Hostel Available')
                            ->badge()
                            ->formatStateUsing(fn (bool $state) => $state ? 'Yes' : 'No')
                            ->color(fn (bool $state) => $state ? 'success' : 'gray'),
                    ]),

                Section::make('Location')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('address')
                            ->label('Address')
                            ->columnSpanFull(),
                        TextEntry::make('village_city')
                            ->label('Village / City')
                            ->placeholder('—'),
                        TextEntry::make('taluka')
                            ->label('Taluka')
                            ->placeholder('—'),
                        TextEntry::make('district')
                            ->label('District'),
                        TextEntry::make('state')
                            ->label('State'),
                        TextEntry::make('pincode')
                            ->label('Pincode')
                            ->placeholder('—'),
                        TextEntry::make('latitude')
                            ->label('Latitude')
                            ->placeholder('—'),
                        TextEntry::make('longitude')
                            ->label('Longitude')
                            ->placeholder('—'),
                    ]),

                Section::make('Dates')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('opening_date')
                            ->label('Opening Date')
                            ->date('d-m-Y')
                            ->placeholder('—'),
                        TextEntry::make('agreement_start_date')
                            ->label('Agreement Start Date')
                            ->date('d-m-Y')
                            ->placeholder('—'),
                        TextEntry::make('agreement_end_date')
                            ->label('Agreement End Date')
                            ->date('d-m-Y')
                            ->placeholder('—'),
                    ]),

                Section::make('Documents')
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('centre_photo')
                            ->label('Centre Photo')
                            ->disk('public')
                            ->placeholder('No photo uploaded'),
                        TextEntry::make('agreement_document')
                            ->label('Agreement Document')
                            ->formatStateUsing(fn (?string $state) => filled($state) ? 'Uploaded' : 'Not uploaded')
                            ->badge()
                            ->color(fn (?string $state) => filled($state) ? 'success' : 'gray'),
                    ]),

                Section::make('Audit')
                    ->columns(3)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('createdBy.name')
                            ->label('Created By')
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('d-m-Y h:i A'),
                        TextEntry::make('updatedBy.name')
                            ->label('Last Updated By')
                            ->placeholder('—'),
                        TextEntry::make('updated_at')
                            ->label('Updated At')
                            ->dateTime('d-m-Y h:i A'),
                    ]),
            ]);
    }
}
