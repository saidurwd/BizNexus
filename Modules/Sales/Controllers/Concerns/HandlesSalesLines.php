<?php

namespace Modules\Sales\Controllers\Concerns;

use Illuminate\Validation\Rule;
use Modules\Core\Models\Currency;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\Tax;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\Warehouse;

/**
 * Line validation and form data shared by the quotation and sales order screens.
 */
trait HandlesSalesLines
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function salesLineRules(int $companyId): array
    {
        return [
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)->where('status', 'active')],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_id' => ['nullable', Rule::exists('taxes', 'id')->where('company_id', $companyId)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function salesLineAttributes(): array
    {
        return [
            'lines.*.product_id' => __('product'),
            'lines.*.quantity' => __('quantity'),
            'lines.*.unit_price' => __('unit price'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function salesFormData(): array
    {
        return [
            'customers' => Customer::where('status', 'active')->orderBy('name')->get(['id', 'customer_code', 'name', 'currency_id']),
            'currencies' => Currency::where('status', 'active')->orderBy('code')->get(),
            'products' => Product::active()->with('unit')->orderBy('sku')->get(['id', 'sku', 'name', 'type', 'unit_id', 'sales_price', 'sales_tax_id', 'stock_quantity']),
            'taxes' => Tax::where('status', 'active')->orderBy('tax_code')->get(['id', 'tax_code', 'tax_name']),
            'warehouses' => Warehouse::active()->orderByDesc('is_default')->orderBy('code')->get(),
        ];
    }
}
