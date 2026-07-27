<?php

namespace App\Filament\Widgets;

use App\Enums\StudentAdmissionStatus;
use App\Enums\StudentCentreVisitStatus;
use App\Enums\StudentVerificationStatus;
use App\Models\Student;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStudentStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 8;

    public static function canView(): bool
    {
        return auth()->user()?->isElevated() ?? false;
    }

    protected function getStats(): array
    {
        $base = Student::query();

        return [
            Stat::make('Total Students', (clone $base)->count()),
            Stat::make('Added Today', (clone $base)->whereDate('created_at', today())->count()),
            Stat::make('Submitted', (clone $base)->whereIn('admission_status', [
                StudentAdmissionStatus::Submitted,
                StudentAdmissionStatus::DocumentVerificationPending,
            ])->count()),
            Stat::make('Verification Pending', (clone $base)->where('verification_status', StudentVerificationStatus::Pending)->count()),
            Stat::make('Documents Verified', (clone $base)->where('verification_status', StudentVerificationStatus::Verified)->count()),
            Stat::make('Centre Visit Pending', (clone $base)->where('centre_visit_status', StudentCentreVisitStatus::VisitPlanned)->count()
                + (clone $base)->where('admission_status', StudentAdmissionStatus::CentreVisitPending)->count()),
            Stat::make('Centre Visited', (clone $base)->whereIn('centre_visit_status', [
                StudentCentreVisitStatus::CentreVisited,
                StudentCentreVisitStatus::VisitConfirmed,
            ])->count()),
            Stat::make('Admission Confirmed', (clone $base)->where('admission_status', StudentAdmissionStatus::AdmissionConfirmed)->count()),
            Stat::make('Joined Students', (clone $base)->where('admission_status', StudentAdmissionStatus::JoinedCentre)->count()),
            Stat::make('Rejected', (clone $base)->where('admission_status', StudentAdmissionStatus::Rejected)->count()),
            Stat::make('Overdue Follow-ups', (clone $base)->overdueFollowUp()->count()),
        ];
    }
}
