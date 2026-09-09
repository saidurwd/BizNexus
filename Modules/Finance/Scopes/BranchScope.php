<?php

namespace Modules\Finance\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

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

        if (!$branchId) {
            return;
        }

        $table = $model->getTable();

        if (in_array($table, $this->branchAwareTables) && Schema::hasColumn($table, 'branch_id')) {
            $builder->where($table . '.branch_id', $branchId);
        }
    }
}
