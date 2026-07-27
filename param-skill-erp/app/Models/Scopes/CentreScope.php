<?php

namespace App\Models\Scopes;

use App\Services\CentreContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CentreScope implements Scope
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

        $centreId = $context->centreId();

        if ($centreId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->getTable().'.centre_id', $centreId);
    }
}
