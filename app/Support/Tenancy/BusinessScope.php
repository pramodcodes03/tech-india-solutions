<?php

namespace App\Support\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BusinessScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $current = app(CurrentBusiness::class);

        if ($current->id()) {
            $builder->where($model->getTable().'.business_id', $current->id());

            return;
        }

        // Fail-closed: with no business resolved, return no rows.
        // Cross-business queries (super-admin reports) must explicitly call
        // ->withoutGlobalScope(BusinessScope::class).
        $builder->whereRaw('1 = 0');
    }
}
