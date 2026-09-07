<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NumberSequence extends Model
{
    protected $fillable = [
        'company_id',
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

    public function getNextNumber(): string
    {
        $this->last_number++;
        $this->save();

        return $this->generateNumber();
    }

    public function generateNumber(): string
    {
        $number = $this->format;
        $year = date('Y');

        $number = str_replace('{PREFIX}', $this->prefix, $number);
        $number = str_replace('{YEAR}', $year, $number);
        $number = str_replace('{MONTH}', date('m'), $number);
        $number = str_replace('{DAY}', date('d'), $number);

        if (preg_match('/\{SEQUENCE:(\d+)\}/', $number, $matches)) {
            $padding = (int) $matches[1];
            $sequence = str_pad($this->last_number, $padding, '0', STR_PAD_LEFT);
            $number = preg_replace('/\{SEQUENCE:\d+\}/', $sequence, $number);
        }

        return $number;
    }

    public static function getNextNumberFor(string $documentType, int $companyId): string
    {
        $sequence = static::where('company_id', $companyId)
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->firstOrFail();

        return $sequence->getNextNumber();
    }

    public static function initializeForCompany(int $companyId, array $documentTypes): void
    {
        foreach ($documentTypes as $type) {
            static::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'document_type' => $type['document_type'],
                ],
                [
                    'prefix' => $type['prefix'],
                    'format' => $type['format'] ?? '{PREFIX}-{YEAR}-{SEQUENCE:6}',
                    'last_number' => 0,
                    'is_active' => true,
                ]
            );
        }
    }
}
