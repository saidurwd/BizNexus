<?php

namespace Modules\Inventory\Support;

use Illuminate\Support\Facades\DB;

/**
 * The standard Purchasing Manager role: runs purchasing end to end (suppliers, purchase orders and their
 * approval, receiving, returns, reorder suggestions) and records the supplier's invoices and credit notes
 * against orders. Approving and posting those invoices, and paying suppliers, stay with finance.
 */
class PurchasingManagerRole
{
    public const SLUG = 'purchasing-manager';

    /**
     * @var list<string>
     */
    public const PERMISSIONS = [
        'core.workflow.view',
        'inventory.purchase-orders.view',
        'inventory.purchase-orders.create',
        'inventory.purchase-orders.submit',
        'inventory.purchase-orders.approve',
        'inventory.purchase-orders.cancel',
        'inventory.goods-receipts.view',
        'inventory.goods-receipts.create',
        'inventory.supplier-returns.create',
        'inventory.products.view',
        'inventory.products.manage',
        'inventory.stock.view',
        'inventory.setup.view',
        'finance.suppliers.view',
        'finance.suppliers.create',
        'finance.suppliers.update',
        'finance.supplier-invoices.view',
        'finance.supplier-invoices.create',
        'finance.supplier-invoices.submit',
        'finance.supplier-credit-notes.view',
        'finance.supplier-credit-notes.create',
        'finance.supplier-credit-notes.submit',
        'finance.payment-terms.view',
    ];

    /**
     * Create the role if it is missing and give it any of its permissions it lacks. Permissions an
     * administrator added or removed later are left as they are, except that missing ones are added back.
     */
    public static function install(): void
    {
        $now = now();
        $roleId = DB::table('roles')->where('slug', self::SLUG)->value('id') ?? DB::table('roles')->insertGetId([
            'name' => 'Purchasing Manager',
            'slug' => self::SLUG,
            'description' => 'Suppliers, purchase orders and their approval, receiving, returns and reordering; records supplier invoices against orders',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('permission_role')->insertOrIgnore(
            DB::table('permissions')->whereIn('slug', self::PERMISSIONS)->pluck('id')
                ->map(fn (int $permissionId) => ['permission_id' => $permissionId, 'role_id' => $roleId, 'created_at' => $now, 'updated_at' => $now])
                ->all()
        );
    }
}
