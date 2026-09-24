<?php

namespace Modules\Finance\Services\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;

trait EnforcesSegregationOfDuties
{
    /**
     * Refuse approval by the user who created the document when finance.controls.creator_cannot_approve is on.
     */
    protected function ensureApproverIsNotCreator(Model $document, string $documentLabel): void
    {
        if (! config('finance.controls.creator_cannot_approve') || $document->created_by === null) {
            return;
        }

        if ((int) $document->created_by === (int) Auth::id()) {
            throw new InvalidAccountingTransactionException("You cannot approve a {$documentLabel} you created.");
        }
    }
}
