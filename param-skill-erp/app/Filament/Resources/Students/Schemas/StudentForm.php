<?php

namespace App\Filament\Resources\Students\Schemas;

use App\Enums\CentreStatus;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Enums\StudentAdmissionStatus;
use App\Enums\StudentCentreVisitStatus;
use App\Enums\StudentVerificationStatus;
use App\Models\Centre;
use App\Models\Employee;
use App\Models\Student;
use App\Services\CentreContext;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        $context = app(CentreContext::class);
        $isManager = $context->isCentreManager() && ! $context->isElevated();

        return $schema
            ->components([
                Section::make('Personal Details')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextInput::make('first_name')->required()->maxLength(100),
                        TextInput::make('middle_name')->maxLength(100),
                        TextInput::make('last_name')->required()->maxLength(100),
                        TextInput::make('father_name')->label('Father Name')->maxLength(150),
                        TextInput::make('mother_name')->label('Mother Name')->maxLength(150),
                        Select::make('gender')->options([
                            'Male' => 'Male',
                            'Female' => 'Female',
                            'Other' => 'Other',
                        ])->native(false),
                        DatePicker::make('date_of_birth')->displayFormat('d-m-Y')->native(false)->maxDate(now()),
                        TextInput::make('mobile')
                            ->required()
                            ->tel()
                            ->maxLength(15)
                            ->rule('regex:/^[6-9]\d{9}$/')
                            ->helperText('10-digit Indian mobile'),
                        TextInput::make('parent_mobile')
                            ->label('Parent Mobile')
                            ->tel()
                            ->maxLength(15)
                            ->rule('nullable|regex:/^[6-9]\d{9}$/'),
                        TextInput::make('alternate_mobile')
                            ->label('Alternate Mobile')
                            ->tel()
                            ->maxLength(15)
                            ->rule('nullable|regex:/^[6-9]\d{9}$/'),
                        TextInput::make('email')->email()->maxLength(255),
                        TextInput::make('aadhaar_number')
                            ->label('Aadhaar Number')
                            ->rule('nullable|regex:/^\d{12}$/')
                            ->helperText(fn (?Student $record) => $record
                                ? "Current: {$record->masked_aadhaar}. Leave blank to keep unchanged."
                                : 'Optional. Exactly 12 digits. Stored securely.'),
                        TextInput::make('guardian_name')->label('Guardian Name')->maxLength(150),
                        TextInput::make('guardian_relation')->label('Guardian Relation')->maxLength(100),
                        TextInput::make('guardian_mobile')
                            ->label('Guardian Mobile')
                            ->tel()
                            ->maxLength(15)
                            ->rule('nullable|regex:/^[6-9]\d{9}$/'),
                    ]),

                Section::make('Address')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Textarea::make('full_address')->label('Full Address')->required()->rows(2)->columnSpanFull(),
                        TextInput::make('village')->required()->maxLength(255),
                        TextInput::make('taluka')->required()->maxLength(255),
                        TextInput::make('district')->required()->maxLength(255),
                        TextInput::make('state')->default('Maharashtra')->required()->maxLength(255),
                        TextInput::make('pincode')->rule('nullable|regex:/^\d{6}$/')->maxLength(10)->helperText('6-digit PIN code'),
                    ]),

                Section::make('Social')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextInput::make('religion')->maxLength(100),
                        TextInput::make('caste')->maxLength(100),
                        TextInput::make('category')->maxLength(50),
                        Select::make('marital_status')->options([
                            'Single' => 'Single',
                            'Married' => 'Married',
                            'Other' => 'Other',
                        ])->native(false),
                    ]),

                Section::make('Education')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextInput::make('education_qualification')->label('Education Qualification')->maxLength(255),
                        TextInput::make('school_college_name')->label('School / College Name')->maxLength(255),
                        TextInput::make('passing_year')->label('Passing Year')->maxLength(10),
                        TextInput::make('percentage_grade')->label('Percentage / Grade')->maxLength(50),
                        TextInput::make('employment_status')->label('Employment Status')->maxLength(100),
                        TextInput::make('annual_family_income')
                            ->label('Annual Family Income')
                            ->numeric()
                            ->prefix('₹')
                            ->minValue(0),
                    ]),

                Section::make('Admission Preference')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextInput::make('preferred_course')->label('Preferred Course')->maxLength(255),
                        Select::make('preferred_centre_id')
                            ->label('Preferred Centre')
                            ->searchable()
                            ->preload()
                            ->options(fn () => Centre::query()
                                ->where('status', CentreStatus::Active)
                                ->orderBy('centre_name')
                                ->pluck('centre_name', 'id'))
                            ->helperText('Only active centres'),
                        Toggle::make('hostel_required')->label('Hostel Required'),
                        Select::make('admission_status')
                            ->label('Admission Status')
                            ->options(StudentAdmissionStatus::options())
                            ->default(StudentAdmissionStatus::Draft->value)
                            ->native(false)
                            ->required(),
                        Select::make('verification_status')
                            ->label('Verification Status')
                            ->options(StudentVerificationStatus::options())
                            ->default(StudentVerificationStatus::Pending->value)
                            ->native(false)
                            ->required(),
                        Select::make('centre_visit_status')
                            ->label('Centre Visit Status')
                            ->options(StudentCentreVisitStatus::options())
                            ->default(StudentCentreVisitStatus::NotPlanned->value)
                            ->native(false)
                            ->required(),
                        DatePicker::make('next_follow_up_date')
                            ->label('Next Follow-up Date')
                            ->displayFormat('d-m-Y')
                            ->native(false),
                        Textarea::make('remark')->rows(2)->columnSpanFull(),
                    ]),

                Section::make('Mobilizer Mapping')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        $isManager
                            ? Hidden::make('centre_id')->default($context->centreId())
                            : Select::make('centre_id')
                                ->label('Centre')
                                ->live()
                                ->searchable()
                                ->preload()
                                ->options(fn () => Centre::query()
                                    ->where('status', CentreStatus::Active)
                                    ->orderBy('centre_name')
                                    ->pluck('centre_name', 'id'))
                                ->helperText('Only active centres'),
                        Select::make('mobilizer_id')
                            ->label('Mobilizer')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(function (Get $get) {
                                $centreId = $get('centre_id');

                                return Employee::query()
                                    ->where('employee_role', EmployeeRole::Mobilizer)
                                    ->where('status', EmployeeStatus::Active)
                                    ->when($centreId, fn ($query) => $query->where('centre_id', $centreId))
                                    ->orderBy('first_name')
                                    ->get()
                                    ->mapWithKeys(fn (Employee $employee) => [
                                        $employee->id => "{$employee->employee_code} — {$employee->full_name}",
                                    ]);
                            })
                            ->helperText('Active Mobilizers from the selected centre'),
                    ]),

                Section::make('Student Photo')
                    ->columns(1)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        FileUpload::make('student_photo')
                            ->label('Student Photo')
                            ->image()
                            ->disk('private')
                            ->directory('students/photos')
                            ->visibility('private')
                            ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png'])
                            ->maxSize(2048),
                    ]),
            ]);
    }
}
