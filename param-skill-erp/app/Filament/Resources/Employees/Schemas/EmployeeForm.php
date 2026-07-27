<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Enums\CentreStatus;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Centre;
use App\Services\CentreContext;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        $context = app(CentreContext::class);
        $isManager = $context->isCentreManager() && ! $context->isElevated();

        return $schema
            ->components([
                Section::make('Personal Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('first_name')->required()->maxLength(100),
                        TextInput::make('middle_name')->maxLength(100),
                        TextInput::make('last_name')->required()->maxLength(100),
                        TextInput::make('father_husband_name')->label('Father / Husband Name')->maxLength(150),
                        TextInput::make('mobile')
                            ->required()
                            ->rule('regex:/^[6-9]\d{9}$/')
                            ->helperText('10-digit Indian mobile'),
                        TextInput::make('alternate_mobile')->rule('nullable|regex:/^[6-9]\d{9}$/'),
                        TextInput::make('email')->email(),
                        Select::make('gender')->options([
                            'male' => 'Male',
                            'female' => 'Female',
                            'other' => 'Other',
                        ])->native(false),
                        DatePicker::make('date_of_birth')->displayFormat('d-m-Y')->native(false),
                    ]),
                Section::make('Identity Details')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextInput::make('aadhaar_number')
                            ->label('Aadhaar Number')
                            ->rule('nullable|regex:/^\d{12}$/')
                            ->helperText('Exactly 12 digits. Stored securely.'),
                        TextInput::make('pan_number')
                            ->label('PAN Number')
                            ->rule('nullable|regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/')
                            ->dehydrateStateUsing(fn (?string $state) => filled($state) ? strtoupper($state) : null)
                            ->helperText('Format: ABCDE1234F'),
                    ]),
                Section::make('Address')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Textarea::make('address')->label('Full Address')->rows(2)->columnSpanFull(),
                        TextInput::make('village'),
                        TextInput::make('taluka'),
                        TextInput::make('district'),
                        TextInput::make('state')->default('Maharashtra'),
                        TextInput::make('pincode')->rule('nullable|regex:/^\d{6}$/'),
                    ]),
                Section::make('Employment')
                    ->columns(2)
                    ->schema([
                        $isManager
                            ? Hidden::make('centre_id')->default($context->centreId())
                            : Select::make('centre_id')
                                ->label('Centre')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->options(fn () => Centre::query()
                                    ->where('status', CentreStatus::Active)
                                    ->orderBy('centre_name')
                                    ->pluck('centre_name', 'id'))
                                ->helperText('Only active centres'),
                        Select::make('employee_role')
                            ->label('Employee Role')
                            ->required()
                            ->options(EmployeeRole::options())
                            ->native(false)
                            ->searchable(),
                        DatePicker::make('joining_date')->displayFormat('d-m-Y')->native(false),
                        TextInput::make('salary')->numeric()->prefix('₹')->minValue(0),
                        Select::make('status')
                            ->options([
                                EmployeeStatus::Active->value => 'Active',
                                EmployeeStatus::Inactive->value => 'Inactive',
                            ])
                            ->default(EmployeeStatus::Active->value)
                            ->required()
                            ->native(false),
                    ]),
                Section::make('Bank Details')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextInput::make('bank_name'),
                        TextInput::make('account_number')->password()->revealable(),
                        TextInput::make('ifsc_code')->label('IFSC Code')->maxLength(20),
                    ]),
                Section::make('Emergency Details')
                    ->collapsed()
                    ->schema([
                        TextInput::make('emergency_contact')->label('Emergency Contact'),
                    ]),
                Section::make('Documents')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        FileUpload::make('profile_photo')->image()->disk('public')->directory('employees/photos')->visibility('public'),
                        FileUpload::make('aadhaar_document')->disk('private')->directory('employees/aadhaar')->visibility('private')->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])->maxSize(5120),
                        FileUpload::make('pan_document')->disk('private')->directory('employees/pan')->visibility('private')->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])->maxSize(5120),
                        FileUpload::make('education_certificate')->disk('private')->directory('employees/education')->visibility('private')->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])->maxSize(5120),
                        FileUpload::make('appointment_letter')->disk('private')->directory('employees/appointment')->visibility('private')->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])->maxSize(5120),
                        FileUpload::make('other_document')->disk('private')->directory('employees/other')->visibility('private')->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])->maxSize(5120),
                    ]),
            ]);
    }
}
