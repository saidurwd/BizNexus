<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxTransaction extends Model
{
    protected $fillable = [
        'company_id',
        'tax_id',
        'transaction_type',
        'journal_line_id',
        'invoice_id',
        'payment_id',
        'taxable_amount',
        'tax_amount',
        'exchange_rate',
        'currency_code',
        'tax_date',
        'reference_number',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tax_date' => 'date',
        'taxable_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'exchange_rate' => 'decimal:6',
    ];

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\Company::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class, 'invoice_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(SupplierPayment::class, 'payment_id');
    }

    public function journalLine(): BelongsTo
    {
        return $this->belongsTo(JournalLine::class);
    }

    public function isInputTax(): bool
    {
        return $this->transaction_type === 'INPUT';
    }

    public function isOutputTax(): bool
    {
        return $this->transaction_type === 'OUTPUT';
    }
}
