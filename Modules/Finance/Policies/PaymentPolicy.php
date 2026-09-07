<?php

namespace Modules\Finance\Policies;

use App\Models\User;
use Modules\Finance\Models\SupplierPayment;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool
    {
        return $user->hasPermission('payment.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payment.create');
    }

    public function approve(User $user): bool
    {
        return $user->hasPermission('payment.approve');
    }

    public function post(User $user): bool
    {
        return $user->hasPermission('payment.post');
    }
}
