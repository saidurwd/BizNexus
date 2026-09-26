<?php

namespace Modules\Core\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NumberSequence extends Model
{
    protected $fillable = [
        'company_id',
        'fiscal_year_id',
        'document_type',
        'prefix',
        'format',
        'last_number',
        'is_active',
    ];

    protected $casts = [
        'last_number' => 'integer',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    /**
     * Advance the sequence. Callers must hold a row lock on this sequence (see DocumentNumberService).
     */
    public function getNextNumber(?CarbonInterface $documentDate = null): string
    {
        $this->last_number++;
        $this->save();

        return $this->generateNumber($documentDate);
    }

    /**
     * Format the current number; date tokens come from the document date rather than today.
     */
    public function generateNumber(?CarbonInterface $documentDate = null): string
    {
        $documentDate ??= now();
        $number = $this->format;

        $number = str_replace('{PREFIX}', $this->prefix, $number);
        $number = str_replace('{YEAR}', $documentDate->format('Y'), $number);
        $number = str_replace('{YY}', $documentDate->format('y'), $number);
        $number = str_replace('{MONTH}', $documentDate->format('m'), $number);
        $number = str_replace('{DAY}', $documentDate->format('d'), $number);

        if (preg_match('/\{SEQUENCE:(\d+)\}/', $number, $matches)) {
            $padding = (int) $matches[1];
            $sequence = str_pad($this->last_number, $padding, '0', STR_PAD_LEFT);
            $number = preg_replace('/\{SEQUENCE:\d+\}/', $sequence, $number);
        }

        return $number;
    }
}
