<?php

namespace App\Services;

use App\Models\User;

/**
 * Placeholder service for Phase 1 centre-based access checks.
 * Full Centre module will plug into canAccessCentre() later.
 */
class CentreAccessService
{
    public function canAccessPanel(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        // Future: block users belonging to inactive centres.
        if ($user->centre_id !== null && ! $this->isCentreActive($user->centre_id)) {
            return false;
        }

        return true;
    }

    public function isCentreActive(?int $centreId): bool
    {
        if ($centreId === null) {
            return true;
        }

        // Centre module not implemented yet — treat unknown centres as active until Phase 1.
        return true;
    }
}
