<?php

namespace Modules\Inventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\Supplier;

/**
 * Goods sent back to the supplier they were received from. Posting takes stock items out of the warehouse
 * and reverses the receipt: goods received not invoiced is debited with what the receipt credited it.
 */
class SupplierReturn extends Model
{
    use BelongsToCompany;

    public const STATUS_POSTED = 'POSTED';

    protected $fillable = ['company_id', 'return_number', 'purchase_order_id', 'supplier_id', 'warehouse_id', 'return_date', 'reason', 'status', 'journal_id', 'created_by'];

    protected $casts = [
        'return_date' => 'date',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SupplierReturnLine::class)->orderBy('id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
