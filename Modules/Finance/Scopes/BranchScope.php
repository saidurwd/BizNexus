<?php

namespace Modules\Finance\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BranchScope implements Scope
{
    protected array $branchAwareTables = [
        'journals',
        'journal_lines',
        'supplier_invoices',
        'customer_invoices',
        'supplier_payments',
        'customer_receipts',
    ];

    public function apply(Builder $builder, Model $model)
    {
        $branchId = session('active_branch_id');

        if (! $branchId) {
            return;
        }

        $table = $model->getTable();

        if (in_array($table, $this->branchAwareTables, true)) {
            $builder->where($table.'.branch_id', $branchId);
        }
    }
}
