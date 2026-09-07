<?php

namespace Modules\Finance\Policies;

use App\Models\User;
use Modules\Finance\Models\CustomerInvoice;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerInvoicePolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool
    {
        return $user->hasPermission('customer_invoice.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('customer_invoice.create');
    }

    public function update(User $user): bool
    {
        return $user->hasPermission('customer_invoice.update');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermission('customer_invoice.delete');
    }

    public function approve(User $user): bool
    {
        return $user->hasPermission('customer_invoice.approve');
    }

    public function post(User $user): bool
    {
        return $user->hasPermission('customer_invoice.post');
    }
}
