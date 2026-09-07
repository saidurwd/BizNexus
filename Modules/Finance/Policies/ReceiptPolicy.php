<?php

namespace Modules\Finance\Policies;

use App\Models\User;
use Modules\Finance\Models\CustomerReceipt;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReceiptPolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool
    {
        return $user->hasPermission('receipt.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('receipt.create');
    }

    public function approve(User $user): bool
    {
        return $user->hasPermission('receipt.approve');
    }

    public function post(User $user): bool
    {
        return $user->hasPermission('receipt.post');
    }
}
