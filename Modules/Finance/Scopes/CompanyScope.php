<?php

namespace Modules\Finance\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $companyId = session('active_company_id');

        if ($companyId && Schema::hasColumn($model->getTable(), 'company_id')) {
            $builder->where($model->getTable() . '.company_id', $companyId);
        }
    }
}
