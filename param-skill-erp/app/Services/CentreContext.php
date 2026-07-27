<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Models\Centre;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CentreContext
{
    public function user(?User $actor = null): ?User
    {
        if ($actor instanceof User) {
            return $actor;
        }

        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    public function isElevated(?User $actor = null): bool
    {
        $user = $this->user($actor);

        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole([
            RoleName::SuperAdmin->value,
            RoleName::Admin->value,
        ]);
    }

    public function isCentreManager(?User $actor = null): bool
    {
        return (bool) $this->user($actor)?->hasRole(RoleName::CentreManager->value);
    }

    public function centreId(?User $actor = null): ?int
    {
        return $this->user($actor)?->centre_id;
    }

    public function centre(?User $actor = null): ?Centre
    {
        $user = $this->user($actor);

        if ($user === null || $user->centre_id === null) {
            return null;
        }

        return $user->centre;
    }

    public function canAccessCentre(?int $centreId, ?User $actor = null): bool
    {
        if ($this->isElevated($actor)) {
            return true;
        }

        if ($centreId === null) {
            return false;
        }

        return $this->centreId($actor) === $centreId;
    }

    public function canAccessEmployee(Employee $employee, ?User $actor = null): bool
    {
        return $this->canAccessCentre($employee->centre_id, $actor);
    }

    public function assertCentreAccess(?int $centreId, ?User $actor = null): void
    {
        if (! $this->canAccessCentre($centreId, $actor)) {
            abort(403, 'You are not authorized to access this centre.');
        }
    }
}
