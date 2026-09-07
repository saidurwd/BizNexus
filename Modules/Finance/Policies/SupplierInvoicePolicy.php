<?php

namespace Modules\Finance\Policies;

use App\Models\User;
use Modules\Finance\Models\SupplierInvoice;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierInvoicePolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool
    {
        return $user->hasPermission('supplier_invoice.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('supplier_invoice.create');
    }

    public function update(User $user): bool
    {
        return $user->hasPermission('supplier_invoice.update');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermission('supplier_invoice.delete');
    }

    public function approve(User $user): bool
    {
        return $user->hasPermission('supplier_invoice.approve');
    }

    public function post(User $user): bool
    {
        return $user->hasPermission('supplier_invoice.post');
    }
}
