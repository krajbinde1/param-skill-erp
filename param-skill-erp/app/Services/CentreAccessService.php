<?php

namespace App\Services;

use App\Enums\CentreStatus;
use App\Enums\EmployeeStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Centre;
use App\Models\Employee;
use App\Models\User;

class CentreAccessService
{
    /**
     * @var list<string>
     */
    protected array $panelRoles = [
        RoleName::SuperAdmin->value,
        RoleName::Admin->value,
        RoleName::CentreManager->value,
    ];

    public function canAccessPanel(User $user): bool
    {
        if (! $user->status?->canLogin()) {
            return false;
        }

        if (! $user->hasAnyRole($this->panelRoles)) {
            return false;
        }

        if ($user->centre_id !== null) {
            $centre = Centre::query()->find($user->centre_id);

            if ($centre === null || $centre->status !== CentreStatus::Active) {
                return false;
            }
        }

        if ($user->employee_id !== null) {
            $employee = Employee::query()->find($user->employee_id);

            if ($employee === null || $employee->status !== EmployeeStatus::Active) {
                return false;
            }
        }

        return true;
    }

    public function denialReason(User $user): ?string
    {
        if ($user->status === UserStatus::Blocked) {
            return 'Your account has been blocked.';
        }

        if ($user->status === UserStatus::Inactive) {
            return 'Your account is inactive.';
        }

        if (! $user->hasAnyRole($this->panelRoles)) {
            return 'You are not authorized to access the admin panel.';
        }

        if ($user->centre_id !== null) {
            $centre = Centre::query()->find($user->centre_id);

            if ($centre === null || $centre->status !== CentreStatus::Active) {
                return 'Your centre is inactive. Please contact the administrator.';
            }
        }

        if ($user->employee_id !== null) {
            $employee = Employee::query()->find($user->employee_id);

            if ($employee === null || $employee->status !== EmployeeStatus::Active) {
                return 'Your employee profile is inactive.';
            }
        }

        return null;
    }

    public function isCentreActive(?int $centreId): bool
    {
        if ($centreId === null) {
            return true;
        }

        return Centre::query()
            ->whereKey($centreId)
            ->where('status', CentreStatus::Active)
            ->exists();
    }
}
