<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Accounting entries for invoices, payments and receipts are created by their Finance services when the
 * document is posted, so the *Approved and JournalPosted events have no accounting listeners.
 */
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
