<?php

namespace Modules\Inventory\Services\Import;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\Tax;
use Modules\Finance\Services\Import\Importer;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\ProductCategory;
use Modules\Inventory\Models\Unit;

/**
 * Products, matched by SKU: new SKUs are created, existing ones updated with the file's values.
 * Stock quantities are not imported here; opening stock is entered as a stock adjustment.
 */
class ProductImporter implements Importer
{
    public function label(): string
    {
        return __('Products');
    }

    public function columns(): array
    {
        return [
            'sku' => [true, __('Unique code')],
            'name' => [true, __('Name')],
            'type' => [false, __('stock, non_stock or service (default stock)')],
            'unit_code' => [true, __('Code from Units of Measure, e.g. EA')],
            'category_code' => [false, __('Code from Product Categories')],
            'barcode' => [false, ''],
            'purchase_price' => [false, ''],
            'sales_price' => [false, ''],
            'purchase_tax_code' => [false, __('Tax code; empty to use the tax rules')],
            'sales_tax_code' => [false, __('Tax code; empty to use the tax rules')],
            'preferred_supplier_code' => [false, ''],
            'reorder_level' => [false, ''],
            'reorder_quantity' => [false, ''],
            'description' => [false, ''],
            'status' => [false, __('active or inactive (default active)')],
        ];
    }

    public function example(): array
    {
        return [
            ['sku' => 'CHAIR-01', 'name' => 'Office chair', 'type' => 'stock', 'unit_code' => 'EA', 'category_code' => '', 'barcode' => '4006381333931', 'purchase_price' => '85.00', 'sales_price' => '149.00', 'purchase_tax_code' => '', 'sales_tax_code' => '', 'preferred_supplier_code' => '', 'reorder_level' => '10', 'reorder_quantity' => '50', 'description' => 'Ergonomic, black', 'status' => 'active'],
            ['sku' => 'INSTALL', 'name' => 'Installation service', 'type' => 'service', 'unit_code' => 'HR', 'category_code' => '', 'barcode' => '', 'purchase_price' => '', 'sales_price' => '60.00', 'purchase_tax_code' => '', 'sales_tax_code' => '', 'preferred_supplier_code' => '', 'reorder_level' => '', 'reorder_quantity' => '', 'description' => '', 'status' => 'active'],
        ];
    }

    public function validate(array $rows, array $options): array
    {
        $lookups = $this->lookups();
        $existing = Product::get(['id', 'sku', 'type', 'stock_quantity', 'stock_value'])->keyBy('sku');
        $seen = [];

        $checked = array_map(function (array $row) use ($lookups, $existing, &$seen) {
            $data = $row['data'];
            $sku = $data['sku'] ?? '';
            $type = strtolower($data['type'] ?? '') ?: Product::TYPE_STOCK;
            $current = $existing->get($sku);
            $code = fn (string $column) => strtoupper($data[$column] ?? '');
            $number = fn (string $column) => ($data[$column] ?? '') !== '' && (! is_numeric($data[$column]) || $data[$column] < 0);

            $errors = array_keys(array_filter([
                __('Code is missing.') => $sku === '',
                __('Name is missing.') => ($data['name'] ?? '') === '',
                __('Code :code appears more than once in the file.', ['code' => $sku]) => isset($seen[$sku]),
                __('Type must be stock, non_stock or service.') => ! in_array($type, Product::TYPES, true),
                __('The type cannot change once the product has stock or appears on documents.') => $current && $current->type !== $type && $current->isInUse(),
                __('Unit :code does not exist.', ['code' => $code('unit_code')]) => ! $lookups['units']->has($code('unit_code')),
                __('Category :code does not exist.', ['code' => $code('category_code')]) => $code('category_code') !== '' && ! $lookups['categories']->has($code('category_code')),
                __('Tax code :code does not exist.', ['code' => $code('purchase_tax_code')]) => $code('purchase_tax_code') !== '' && ! $lookups['taxes']->has($code('purchase_tax_code')),
                __('Tax code :code does not exist.', ['code' => $code('sales_tax_code')]) => $code('sales_tax_code') !== '' && ! $lookups['taxes']->has($code('sales_tax_code')),
                __('Supplier :code does not exist.', ['code' => $data['preferred_supplier_code'] ?? '']) => ($data['preferred_supplier_code'] ?? '') !== '' && ! $lookups['suppliers']->has($data['preferred_supplier_code']),
                __('Prices and reorder quantities must be numbers of zero or more.') => $number('purchase_price') || $number('sales_price') || $number('reorder_level') || $number('reorder_quantity'),
                __('Status must be active or inactive.') => ! in_array(strtolower($data['status'] ?? ''), ['', 'active', 'inactive'], true),
            ]));
            $seen[$sku] = true;

            return [...$row, 'action' => $current ? 'update' : 'create', 'errors' => $errors];
        }, $rows);

        return ['rows' => $checked, 'errors' => []];
    }

    public function import(array $rows, array $options): string
    {
        $lookups = $this->lookups();
        $counts = ['create' => 0, 'update' => 0];

        foreach ($rows as $row) {
            $data = $row['data'];
            $optional = fn (string $key) => ($data[$key] ?? '') !== '' ? $data[$key] : null;
            $code = fn (string $column) => strtoupper($data[$column] ?? '');

            $product = Product::firstOrNew(['sku' => $data['sku']], ['created_by' => Auth::id(), 'costing_method' => Product::COSTING_WEIGHTED_AVERAGE]);
            $product->fill([
                'company_id' => (int) app(CompanyContextService::class)->getActiveCompanyId(),
                'name' => $data['name'],
                'type' => strtolower($optional('type') ?? Product::TYPE_STOCK),
                'unit_id' => $lookups['units']->get($code('unit_code')),
                'category_id' => $optional('category_code') ? $lookups['categories']->get($code('category_code')) : null,
                'barcode' => $optional('barcode'),
                'purchase_price' => $optional('purchase_price') ?? 0,
                'sales_price' => $optional('sales_price') ?? 0,
                'purchase_tax_id' => $optional('purchase_tax_code') ? $lookups['taxes']->get($code('purchase_tax_code')) : null,
                'sales_tax_id' => $optional('sales_tax_code') ? $lookups['taxes']->get($code('sales_tax_code')) : null,
                'preferred_supplier_id' => $optional('preferred_supplier_code') ? $lookups['suppliers']->get($data['preferred_supplier_code']) : null,
                'reorder_level' => $optional('reorder_level'),
                'reorder_quantity' => $optional('reorder_quantity'),
                'description' => $optional('description'),
                'status' => strtolower($optional('status') ?? 'active'),
                'updated_by' => Auth::id(),
            ])->save();
            $counts[$row['action']]++;
        }

        return __(':created created, :updated updated.', ['created' => $counts['create'], 'updated' => $counts['update']]);
    }

    /**
     * @return array{units: Collection<string, int>, categories: Collection<string, int>, taxes: Collection<string, int>, suppliers: Collection<string, int>}
     */
    protected function lookups(): array
    {
        return [
            'units' => Unit::pluck('id', 'code')->mapWithKeys(fn (int $id, string $code) => [strtoupper($code) => $id]),
            'categories' => ProductCategory::pluck('id', 'code')->mapWithKeys(fn (int $id, string $code) => [strtoupper($code) => $id]),
            'taxes' => Tax::pluck('id', 'tax_code')->mapWithKeys(fn (int $id, string $code) => [strtoupper($code) => $id]),
            'suppliers' => Supplier::pluck('id', 'supplier_code'),
        ];
    }
}
