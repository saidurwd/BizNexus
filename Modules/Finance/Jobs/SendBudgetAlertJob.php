<?php

namespace Modules\Finance\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Finance\Notifications\BudgetExceededNotification;
use Modules\Finance\Models\Budget;
use App\Models\User;

class SendBudgetAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $budgetId,
        public float $actualSpending,
        public float $budgetAmount,
        public ?int $notifyUserId = null
    ) {}

    public function handle(): void
    {
        $budget = Budget::with('budgetLines.account')->findOrFail($this->budgetId);

        if ($this->notifyUserId) {
            $user = User::findOrFail($this->notifyUserId);
            $user->notify(new BudgetExceededNotification($budget, $this->actualSpending, $this->budgetAmount));
        } else {
            $users = User::whereHas('roles', fn($q) => $q->whereIn('name', ['Finance Manager', 'CFO', 'Accountant']))->get();
            foreach ($users as $user) {
                $user->notify(new BudgetExceededNotification($budget, $this->actualSpending, $this->budgetAmount));
            }
        }
    }
}
