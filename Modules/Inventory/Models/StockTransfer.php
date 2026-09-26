<?php

namespace Modules\Inventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Concerns\BelongsToCompany;

/**
 * Stock moved between two warehouses of the company. Value is unchanged (the average cost is company-wide),
 * so a transfer posts no journal.
 */
class StockTransfer extends Model
{
    use BelongsToCompany;

    public const STATUS_POSTED = 'POSTED';

    protected $fillable = ['company_id', 'transfer_number', 'from_warehouse_id', 'to_warehouse_id', 'transfer_date', 'notes', 'status', 'created_by'];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockTransferLine::class)->orderBy('id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
