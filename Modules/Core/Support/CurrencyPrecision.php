<?php

namespace Modules\Core\Support;

use Modules\Core\Models\Currency;

/**
 * Minor units (decimal places) per ISO 4217 currency code, taken from the currencies table with an ISO
 * fallback for codes that are not configured.
 */
class CurrencyPrecision
{
    /**
     * ISO 4217 currencies whose minor units differ from 2.
     */
    public const ISO_MINOR_UNITS = [
        'BIF' => 0, 'CLP' => 0, 'DJF' => 0, 'GNF' => 0, 'ISK' => 0, 'JPY' => 0, 'KMF' => 0, 'KRW' => 0,
        'PYG' => 0, 'RWF' => 0, 'UGX' => 0, 'UYI' => 0, 'VND' => 0, 'VUV' => 0, 'XAF' => 0, 'XOF' => 0, 'XPF' => 0,
        'BHD' => 3, 'IQD' => 3, 'JOD' => 3, 'KWD' => 3, 'LYD' => 3, 'OMR' => 3, 'TND' => 3,
        'CLF' => 4, 'UYW' => 4,
    ];

    /**
     * @var array<string, int>
     */
    protected static array $resolved = [];

    public static function for(string $currencyCode): int
    {
        $currencyCode = strtoupper($currencyCode);

        return static::$resolved[$currencyCode] ??= Currency::where('code', $currencyCode)->value('decimal_places')
            ?? self::ISO_MINOR_UNITS[$currencyCode]
            ?? 2;
    }

    public static function flush(): void
    {
        static::$resolved = [];
    }
}
