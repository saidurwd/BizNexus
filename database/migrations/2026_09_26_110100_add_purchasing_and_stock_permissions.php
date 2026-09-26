<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\PermissionCatalog;

return new class extends Migration
{
    public function up(): void
    {
        PermissionCatalog::install();
    }

    public function down(): void
    {
        DB::table('permissions')->where(fn ($query) => $query
            ->where('slug', 'like', 'inventory.purchase-orders.%')
            ->orWhere('slug', 'like', 'inventory.goods-receipts.%')
            ->orWhere('slug', 'like', 'inventory.adjustments.%')
            ->orWhereIn('slug', ['inventory.stock.view', 'inventory.transfers.create']))
            ->delete();
    }
};
