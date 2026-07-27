<?php

namespace App\Filament\Resources\Centres\Schemas;

use App\Enums\CentreStatus;
use App\Support\SensitiveData;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CentreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Centre Information')
                    ->description('Basic identity and manager contact details for the centre.')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextInput::make('centre_code')
                            ->label('Centre Code')
                            ->disabled()
                            ->dehydrated(false)
                            ->hiddenOn('create')
                            ->helperText('Auto-generated on creation.'),
                        TextInput::make('centre_name')
                            ->label('Centre Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        TextInput::make('scheme_name')
                            ->label('Scheme Name')
                            ->maxLength(255),
                        TextInput::make('project_name')
                            ->label('Project Name')
                            ->maxLength(255),
                        TextInput::make('manager_name')
                            ->label('Manager Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('manager_mobile')
                            ->label('Manager Mobile')
                            ->required()
                            ->tel()
                            ->maxLength(15)
                            ->rule(
                                fn () => function (string $attribute, mixed $value, Closure $fail): void {
                                    if (filled($value) && ! SensitiveData::isValidIndianMobile((string) $value)) {
                                        $fail('Enter a valid 10-digit Indian mobile number.');
                                    }
                                }
                            )
                            ->helperText('10-digit Indian mobile number, e.g. 9876543210.'),
                        TextInput::make('manager_email')
                            ->label('Manager Email')
                            ->email()
                            ->maxLength(255),
                    ]),

                Section::make('Capacity')
                    ->description('Total intake capacity and hostel availability.')
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextInput::make('centre_capacity')
                            ->label('Total Capacity')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->live(onBlur: true),
                        TextInput::make('boys_capacity')
                            ->label('Boys Capacity')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->rule(
                                fn (Get $get) => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    $total = $get('centre_capacity');

                                    if (blank($total)) {
                                        return;
                                    }

                                    $boys = (int) ($value ?? 0);
                                    $girls = (int) ($get('girls_capacity') ?? 0);

                                    if (($boys + $girls) > (int) $total) {
                                        $fail('Boys capacity plus girls capacity cannot exceed the total centre capacity.');
                                    }
                                }
                            ),
                        TextInput::make('girls_capacity')
                            ->label('Girls Capacity')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->rule(
                                fn (Get $get) => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    $total = $get('centre_capacity');

                                    if (blank($total)) {
                                        return;
                                    }

                                    $boys = (int) ($get('boys_capacity') ?? 0);
                                    $girls = (int) ($value ?? 0);

                                    if (($boys + $girls) > (int) $total) {
                                        $fail('Boys capacity plus girls capacity cannot exceed the total centre capacity.');
                                    }
                                }
                            ),
                        Toggle::make('hostel_available')
                            ->label('Hostel Available')
                            ->columnSpanFull(),
                    ]),

                Section::make('Location')
                    ->description('Address and geographic coordinates of the centre.')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Textarea::make('address')
                            ->label('Address')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('village_city')
                            ->label('Village / City')
                            ->maxLength(255),
                        TextInput::make('taluka')
                            ->label('Taluka')
                            ->maxLength(255),
                        TextInput::make('district')
                            ->label('District')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('state')
                            ->label('State')
                            ->default('Maharashtra')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('pincode')
                            ->label('Pincode')
                            ->required()
                            ->rule('regex:/^\d{6}$/')
                            ->maxLength(6)
                            ->helperText('6-digit pincode.'),
                        TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->minValue(-90)
                            ->maxValue(90),
                        TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->minValue(-180)
                            ->maxValue(180),
                    ]),

                Section::make('Dates')
                    ->description('Opening and agreement validity dates.')
                    ->columns(3)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        DatePicker::make('opening_date')
                            ->label('Opening Date')
                            ->native(false),
                        DatePicker::make('agreement_start_date')
                            ->label('Agreement Start Date')
                            ->native(false)
                            ->live(),
                        DatePicker::make('agreement_end_date')
                            ->label('Agreement End Date')
                            ->native(false)
                            ->rule('after_or_equal:agreement_start_date')
                            ->helperText('Must be on or after the agreement start date.'),
                    ]),

                Section::make('Documents')
                    ->description('Centre photo and signed agreement document.')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        FileUpload::make('centre_photo')
                            ->label('Centre Photo')
                            ->image()
                            ->disk('public')
                            ->directory('centres/photos')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png'])
                            ->maxSize(5120)
                            ->helperText('JPG or PNG, up to 5 MB.'),
                        FileUpload::make('agreement_document')
                            ->label('Agreement Document')
                            ->disk('private')
                            ->directory('centres/agreements')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'])
                            ->maxSize(10240)
                            ->downloadable()
                            ->openable()
                            ->helperText('PDF, JPG or PNG, up to 10 MB.'),
                    ]),

                Section::make('Status')
                    ->description('Current operational status of the centre.')
                    ->columns(1)
                    ->collapsible()
                    ->hiddenOn('create')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(collect(CentreStatus::cases())->mapWithKeys(
                                fn (CentreStatus $status) => [$status->value => $status->label()]
                            ))
                            ->native(false)
                            ->required(),
                    ]),
            ]);
    }
}
