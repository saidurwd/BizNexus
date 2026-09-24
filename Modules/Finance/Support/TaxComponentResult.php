<?php

namespace Modules\Finance\Support;

use Modules\Core\Support\Money;
use Modules\Finance\Models\Tax;

final class TaxComponentResult
{
    public function __construct(
        public readonly Tax $tax,
        public readonly string $rate,
        public readonly Money $taxableBase,
        public readonly Money $amount,
    ) {}

    public function isRecoverable(): bool
    {
        return (bool) $this->tax->is_recoverable;
    }
}
