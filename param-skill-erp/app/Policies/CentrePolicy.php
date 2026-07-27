<?php

namespace App\Policies;

use App\Models\Centre;
use App\Models\User;

class CentrePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('centres.view_any');
    }

    public function view(User $user, Centre $centre): bool
    {
        if (! $user->can('centres.view')) {
            return false;
        }

        if ($user->isElevated()) {
            return true;
        }

        return $user->isCentreManager() && $user->centre_id === $centre->id;
    }

    public function create(User $user): bool
    {
        return $user->can('centres.create');
    }

    public function update(User $user, Centre $centre): bool
    {
        if ($user->can('centres.update') && $user->isElevated()) {
            return true;
        }

        return $user->isCentreManager()
            && $user->centre_id === $centre->id
            && $user->can('centres.view');
    }

    public function delete(User $user, Centre $centre): bool
    {
        return $user->can('centres.delete');
    }

    public function restore(User $user, Centre $centre): bool
    {
        return $user->can('centres.restore');
    }

    public function forceDelete(User $user, Centre $centre): bool
    {
        return false;
    }

    public function activate(User $user, Centre $centre): bool
    {
        return $user->can('centres.activate');
    }

    public function deactivate(User $user, Centre $centre): bool
    {
        return $user->can('centres.deactivate');
    }

    public function resetLogin(User $user, Centre $centre): bool
    {
        return $user->can('centres.reset_login');
    }
}
