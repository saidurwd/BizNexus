<?php

namespace Modules\Finance\Policies;

use App\Models\User;
use Modules\Finance\Models\Journal;
use Illuminate\Auth\Access\HandlesAuthorization;

class JournalPolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool
    {
        return $user->hasPermission('journal.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('journal.create');
    }

    public function update(User $user): bool
    {
        return $user->hasPermission('journal.update');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermission('journal.delete');
    }

    public function submit(User $user): bool
    {
        return $user->hasPermission('journal.submit');
    }

    public function approve(User $user): bool
    {
        return $user->hasPermission('journal.approve');
    }

    public function post(User $user): bool
    {
        return $user->hasPermission('journal.post');
    }

    public function reverse(User $user): bool
    {
        return $user->hasPermission('journal.reverse');
    }
}
