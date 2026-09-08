<?php

namespace Modules\Finance\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $branchId = session('active_branch_id');

        if ($branchId && in_array($model->getTable(), ['journals', 'journal_lines', 'supplier_invoices', 'customer_invoices', 'supplier_payments', 'customer_receipts'])) {
            $builder->where($model->getTable() . '.branch_id', $branchId);
        }
    }
}
