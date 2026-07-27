<?php

namespace App\Models\Concerns;

use App\Models\Scopes\CentreScope;

trait BelongsToCentre
{
    public static function bootBelongsToCentre(): void
    {
        static::addGlobalScope(new CentreScope);
    }
}
