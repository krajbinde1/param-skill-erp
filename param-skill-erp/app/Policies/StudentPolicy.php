<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use App\Services\CentreContext;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('students.view_any');
    }

    public function view(User $user, Student $student): bool
    {
        return $user->can('students.view') && $this->visible($user, $student);
    }

    public function create(User $user): bool
    {
        return $user->can('students.create');
    }

    public function update(User $user, Student $student): bool
    {
        return $user->can('students.update') && $this->visible($user, $student);
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->can('students.delete') && $this->visible($user, $student);
    }

    public function restore(User $user, Student $student): bool
    {
        return $user->can('students.restore') && $this->visible($user, $student);
    }

    public function verifyDocuments(User $user, Student $student): bool
    {
        return $user->can('students.verify_documents') && $this->visible($user, $student);
    }

    public function changeAdmissionStatus(User $user, Student $student): bool
    {
        return $user->can('students.change_admission_status') && $this->visible($user, $student);
    }

    public function confirmCentreVisit(User $user, Student $student): bool
    {
        return $user->can('students.confirm_centre_visit') && $this->visible($user, $student);
    }

    public function reassign(User $user, Student $student): bool
    {
        return $user->can('students.reassign') && $user->isElevated();
    }

    public function export(User $user): bool
    {
        return $user->can('students.export');
    }

    protected function visible(User $user, Student $student): bool
    {
        if ($user->isElevated()) {
            return true;
        }

        if ($user->isCentreManager()) {
            return app(CentreContext::class)->canAccessCentre($student->centre_id, $user)
                || app(CentreContext::class)->canAccessCentre($student->preferred_centre_id, $user)
                || $student->mobilizer?->centre_id === $user->centre_id;
        }

        return $user->employee_id !== null && $student->mobilizer_id === $user->employee_id;
    }
}
