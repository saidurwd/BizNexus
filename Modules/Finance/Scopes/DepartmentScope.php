<?php

namespace Modules\Finance\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class DepartmentScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $departmentId = session('active_department_id');

        if ($departmentId && in_array($model->getTable(), ['journal_lines'])) {
            $builder->where($model->getTable() . '.department_id', $departmentId);
        }
    }
}
