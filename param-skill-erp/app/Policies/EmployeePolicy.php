<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use App\Services\CentreContext;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employees.view_any');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->can('employees.view')
            && app(CentreContext::class)->canAccessEmployee($employee);
    }

    public function create(User $user): bool
    {
        return $user->can('employees.create');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('employees.update')
            && app(CentreContext::class)->canAccessEmployee($employee);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->can('employees.delete')
            && app(CentreContext::class)->canAccessEmployee($employee);
    }

    public function restore(User $user, Employee $employee): bool
    {
        return $user->can('employees.restore')
            && app(CentreContext::class)->canAccessEmployee($employee);
    }

    public function forceDelete(User $user, Employee $employee): bool
    {
        return false;
    }

    public function activate(User $user, Employee $employee): bool
    {
        return $user->can('employees.activate')
            && app(CentreContext::class)->canAccessEmployee($employee);
    }

    public function deactivate(User $user, Employee $employee): bool
    {
        return $user->can('employees.deactivate')
            && app(CentreContext::class)->canAccessEmployee($employee);
    }

    public function resetLogin(User $user, Employee $employee): bool
    {
        return $user->can('employees.reset_login')
            && app(CentreContext::class)->canAccessEmployee($employee);
    }

    public function downloadDocuments(User $user, Employee $employee): bool
    {
        return $user->can('employees.download_documents')
            && app(CentreContext::class)->canAccessEmployee($employee);
    }
}
