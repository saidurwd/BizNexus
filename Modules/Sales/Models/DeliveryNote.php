<?php

namespace Modules\Sales\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\Journal;
use Modules\Inventory\Models\Warehouse;

/**
 * Goods shipped to a customer against a sales order. Posting takes stock items out of the warehouse at the
 * average cost and moves that cost from inventory to goods delivered not invoiced until the invoice posts.
 */
class DeliveryNote extends Model
{
    use BelongsToCompany;

    public const STATUS_POSTED = 'POSTED';

    protected $fillable = [
        'company_id',
        'delivery_number',
        'sales_order_id',
        'customer_id',
        'warehouse_id',
        'delivery_date',
        'carrier',
        'tracking_number',
        'notes',
        'status',
        'journal_id',
        'created_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
        return $this->hasMany(DeliveryNoteLine::class)->orderBy('id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
