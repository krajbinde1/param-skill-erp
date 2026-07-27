<?php

namespace App\Filament\Resources\Students\Schemas;

use App\Support\Format;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StudentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('student_code')->label('Code'),
                        TextEntry::make('full_name')->label('Name'),
                        TextEntry::make('gender'),
                        TextEntry::make('father_name')->label('Father Name'),
                        TextEntry::make('mother_name')->label('Mother Name'),
                        TextEntry::make('date_of_birth')->label('Date of Birth')->formatStateUsing(fn ($state) => Format::date($state)),
                    ]),

                Section::make('Contact')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('mobile'),
                        TextEntry::make('parent_mobile')->label('Parent Mobile'),
                        TextEntry::make('alternate_mobile')->label('Alternate Mobile'),
                        TextEntry::make('email'),
                        TextEntry::make('masked_aadhaar')->label('Aadhaar'),
                        TextEntry::make('guardian_name')->label('Guardian Name'),
                        TextEntry::make('guardian_relation')->label('Guardian Relation'),
                        TextEntry::make('guardian_mobile')->label('Guardian Mobile'),
                    ]),

                Section::make('Address')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('full_address')->label('Full Address')->columnSpanFull(),
                        TextEntry::make('village'),
                        TextEntry::make('taluka'),
                        TextEntry::make('district'),
                        TextEntry::make('state'),
                        TextEntry::make('pincode'),
                    ]),

                Section::make('Education')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('education_qualification')->label('Education Qualification'),
                        TextEntry::make('school_college_name')->label('School / College Name'),
                        TextEntry::make('passing_year')->label('Passing Year'),
                        TextEntry::make('percentage_grade')->label('Percentage / Grade'),
                        TextEntry::make('employment_status')->label('Employment Status'),
                        TextEntry::make('annual_family_income')->label('Annual Family Income')->formatStateUsing(fn ($state) => Format::currency($state)),
                    ]),

                Section::make('Admission Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('admission_status')->badge(),
                        TextEntry::make('verification_status')->badge(),
                        TextEntry::make('centre_visit_status')->badge(),
                        TextEntry::make('preferred_course')->label('Preferred Course'),
                        TextEntry::make('preferredCentre.centre_name')->label('Preferred Centre'),
                        TextEntry::make('hostel_required')->label('Hostel Required')->badge(),
                        TextEntry::make('next_follow_up_date')->label('Next Follow-up')->formatStateUsing(fn ($state) => Format::date($state)),
                        TextEntry::make('rejection_reason')->label('Rejection Reason')->columnSpanFull(),
                        TextEntry::make('remark')->columnSpanFull(),
                    ]),

                Section::make('Mobilizer Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('centre.centre_name')->label('Centre'),
                        TextEntry::make('mobilizer.full_name')->label('Mobilizer'),
                        TextEntry::make('mobilizer.mobile')->label('Mobilizer Mobile'),
                    ]),

                Section::make('Documents')
                    ->schema([
                        RepeatableEntry::make('documents')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('document_type')->label('Type'),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('original_name')->label('File'),
                                TextEntry::make('verified_at')->label('Verified At')->formatStateUsing(fn ($state) => Format::datetime($state)),
                            ])
                            ->columns(4),
                    ])
                    ->collapsible(),

                Section::make('Follow-ups')
                    ->schema([
                        RepeatableEntry::make('followUps')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('follow_up_date')->label('Date')->formatStateUsing(fn ($state) => Format::date($state)),
                                TextEntry::make('contacted')->label('Contacted')->badge(),
                                TextEntry::make('interest_status')->label('Interest'),
                                TextEntry::make('next_follow_up_date')->label('Next Follow-up')->formatStateUsing(fn ($state) => Format::date($state)),
                                TextEntry::make('remark'),
                            ])
                            ->columns(5),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Status History')
                    ->schema([
                        RepeatableEntry::make('statusHistories')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('status_type')->label('Type'),
                                TextEntry::make('old_status')->label('From'),
                                TextEntry::make('new_status')->label('To'),
                                TextEntry::make('remark'),
                                TextEntry::make('changed_at')->label('Changed At')->formatStateUsing(fn ($state) => Format::datetime($state)),
                            ])
                            ->columns(5),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Centre Visits')
                    ->schema([
                        RepeatableEntry::make('centreVisits')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('centre.centre_name')->label('Centre'),
                                TextEntry::make('visit_date')->label('Visit Date')->formatStateUsing(fn ($state) => Format::date($state)),
                                TextEntry::make('visit_status')->label('Status')->badge(),
                                TextEntry::make('manager_remark')->label('Manager Remark'),
                                TextEntry::make('confirmed_at')->label('Confirmed At')->formatStateUsing(fn ($state) => Format::datetime($state)),
                            ])
                            ->columns(5),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
