<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\BelongsToCompany;

/**
 * Tax determination rule: which tax code (and whether reverse charge applies) for a direction, counterparty
 * country and type, and supply type. The most specific matching rule with the lowest priority wins.
 */
class TaxRule extends Model
{
    use BelongsToCompany;

    public const DIRECTION_SALES = 'sales';

    public const DIRECTION_PURCHASE = 'purchase';

    protected $fillable = [
        'company_id', 'direction', 'counterparty_country', 'counterparty_type', 'supply_type', 'tax_id', 'reverse_charge', 'priority',
    ];

    protected $casts = [
        'reverse_charge' => 'boolean',
        'priority' => 'integer',
    ];

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }
}
