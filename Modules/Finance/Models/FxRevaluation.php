<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\BelongsToCompany;

class FxRevaluation extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'revaluation_date',
        'journal_id',
        'reversal_journal_id',
        'net_gain_loss',
        'created_by',
    ];

    protected $casts = [
        'revaluation_date' => 'date',
        'net_gain_loss' => 'decimal:4',
    ];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function reversalJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'reversal_journal_id');
    }
}
