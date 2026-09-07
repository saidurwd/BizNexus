<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliation extends Model
{
    protected $fillable = [
        'company_id',
        'bank_account_id',
        'statement_date',
        'statement_balance',
        'book_balance',
        'difference',
        'status',
        'reconciled_by',
        'reconciled_at',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'statement_balance' => 'decimal:4',
        'book_balance' => 'decimal:4',
        'difference' => 'decimal:4',
        'reconciled_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_RECONCILED = 'RECONCILED';
    public const STATUS_DISPUTED = 'DISPUTED';

    public function company(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\Company::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function reconciledBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'reconciled_by');
    }

    public function isReconciled(): bool
    {
        return $this->status === self::STATUS_RECONCILED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function calculateDifference(): void
    {
        $this->difference = (float) bcsub($this->statement_balance, $this->book_balance, 4);

        if (bccomp($this->difference, 0, 4) === 0) {
            $this->status = self::STATUS_RECONCILED;
        } else {
            $this->status = self::STATUS_DISPUTED;
        }
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeReconciled($query)
    {
        return $query->where('status', self::STATUS_RECONCILED);
    }
}
