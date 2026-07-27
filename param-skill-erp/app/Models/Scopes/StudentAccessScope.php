<?php

namespace App\Models\Scopes;

use App\Enums\RoleName;
use App\Models\Employee;
use App\Services\CentreContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class StudentAccessScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! auth()->check()) {
            return;
        }

        $context = app(CentreContext::class);

        if ($context->isElevated()) {
            return;
        }

        $user = $context->user();

        if ($user === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        if ($user->hasRole(RoleName::CentreManager->value)) {
            $centreId = $user->centre_id;

            if ($centreId === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $mobilizerIds = Employee::withoutGlobalScopes()
                ->where('centre_id', $centreId)
                ->pluck('id');

            $builder->where(function (Builder $query) use ($centreId, $mobilizerIds): void {
                $query->where('centre_id', $centreId)
                    ->orWhere('preferred_centre_id', $centreId)
                    ->orWhereIn('mobilizer_id', $mobilizerIds);
            });

            return;
        }

        if ($user->hasRole(RoleName::Mobilizer->value) && $user->employee_id) {
            $builder->where('mobilizer_id', $user->employee_id);

            return;
        }

        $builder->whereRaw('1 = 0');
    }
}
