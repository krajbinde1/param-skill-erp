<?php

namespace App\Filament\Widgets;

use App\Enums\RoleName;
use App\Enums\StudentAdmissionStatus;
use App\Enums\StudentVerificationStatus;
use App\Models\Student;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CentreManagerStudentStats extends StatsOverviewWidget
{
    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->hasRole(RoleName::CentreManager->value) && $user?->centre_id);
    }

    protected function getStats(): array
    {
        $base = Student::query();

        return [
            Stat::make('Total Students', (clone $base)->count()),
            Stat::make('Added Today', (clone $base)->whereDate('created_at', today())->count()),
            Stat::make('Verification Pending', (clone $base)->whereIn('verification_status', [
                StudentVerificationStatus::Pending,
                StudentVerificationStatus::UnderVerification,
            ])->count()),
            Stat::make('Centre Visit Pending', (clone $base)->where('admission_status', StudentAdmissionStatus::CentreVisitPending)->count()),
            Stat::make('Admission Confirmed', (clone $base)->where('admission_status', StudentAdmissionStatus::AdmissionConfirmed)->count()),
            Stat::make('Joined', (clone $base)->where('admission_status', StudentAdmissionStatus::JoinedCentre)->count()),
            Stat::make('Overdue Follow-ups', (clone $base)->overdueFollowUp()->count()),
        ];
    }
}
