<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Support\Format;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Employee')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('employee_code')->label('Code'),
                        TextEntry::make('full_name')->label('Name'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('centre.centre_name')->label('Centre'),
                        TextEntry::make('mobile'),
                        TextEntry::make('employee_role')->label('Role'),
                        TextEntry::make('district'),
                        TextEntry::make('joining_date')->formatStateUsing(fn ($state) => Format::date($state)),
                        TextEntry::make('masked_aadhaar')->label('Aadhaar'),
                        TextEntry::make('masked_pan')->label('PAN'),
                        TextEntry::make('masked_account_number')->label('Account'),
                        TextEntry::make('salary')->formatStateUsing(fn ($state) => Format::currency($state)),
                        TextEntry::make('created_at')->formatStateUsing(fn ($state) => Format::date($state)),
                    ]),
            ]);
    }
}
