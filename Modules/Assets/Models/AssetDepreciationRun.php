<?php

namespace Modules\Assets\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Finance\Models\Journal;

/**
 * One month's depreciation for the company, posted as a single journal.
 */
class AssetDepreciationRun extends Model
{
    use BelongsToCompany;

    public const STATUS_POSTED = 'POSTED';

    public const STATUS_REVERSED = 'REVERSED';

    protected $fillable = ['company_id', 'period_end', 'total_amount', 'asset_count', 'status', 'journal_id', 'reversal_journal_id', 'created_by'];

    protected $casts = [
        'period_end' => 'date',
        'total_amount' => 'decimal:4',
        'asset_count' => 'integer',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(AssetTransaction::class, 'depreciation_run_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function reversalJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'reversal_journal_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
