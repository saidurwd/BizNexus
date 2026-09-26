<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Models\Company;
use Modules\Finance\Models\Account;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\ProductCategory;
use Modules\Inventory\Models\Unit;
use Modules\Inventory\Models\Warehouse;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->units = Unit::withoutGlobalScopes()->where('company_id', $this->company->id)->get()->keyBy('code');
    $this->user = companyUser(['inventory.products.view', 'inventory.products.manage', 'inventory.setup.view', 'inventory.setup.manage'], $this->company);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function productInput(array $overrides = []): array
{
    return ['sku' => 'CHAIR-01', 'name' => 'Office chair', 'type' => 'stock', 'unit_id' => test()->units['EA']->id, 'purchase_price' => '85', 'sales_price' => '149', 'status' => 'active', ...$overrides];
}

test('a new company gets the usual units of measure and a default main warehouse', function () {
    $warehouses = Warehouse::withoutGlobalScopes()->where('company_id', $this->company->id)->get();

    expect($this->units->keys()->sort()->values()->all())->toBe(['BOX', 'EA', 'HR', 'KG', 'L', 'M'])
        ->and($this->units['KG']->decimals)->toBe(3)
        ->and($warehouses)->toHaveCount(1)
        ->and($warehouses->first())->code->toBe('MAIN')->is_default->toBeTrue();
});

test('products are created and edited, and take the ledger accounts their category does not override', function () {
    $stock = Account::factory()->asset()->create(['company_id' => $this->company->id, 'account_code' => '1300', 'account_name' => 'Stock']);
    $cogs = Account::factory()->expense()->create(['company_id' => $this->company->id, 'account_code' => '5000', 'account_name' => 'Cost of sales']);
    $ownRevenue = Account::factory()->revenue()->create(['company_id' => $this->company->id, 'account_code' => '4100', 'account_name' => 'Furniture sales']);
    $category = ProductCategory::withoutGlobalScopes()->create(['company_id' => $this->company->id, 'code' => 'FURN', 'name' => 'Furniture', 'inventory_account_id' => $stock->id, 'cogs_account_id' => $cogs->id, 'status' => 'active']);

    actingInCompany($this->user, $this->company)->post(route('inventory.products.store'), productInput(['category_id' => $category->id, 'revenue_account_id' => $ownRevenue->id, 'reorder_level' => '5']))
        ->assertSessionHasNoErrors();
    $product = Product::withoutGlobalScopes()->sole();

    expect($product->accountIdFor('inventory'))->toBe($stock->id)
        ->and($product->accountIdFor('cogs'))->toBe($cogs->id)
        ->and($product->accountIdFor('revenue'))->toBe($ownRevenue->id)
        ->and($product->accountIdFor('expense'))->toBeNull()
        ->and($product->created_by)->toBe($this->user->id);

    actingInCompany($this->user, $this->company)->put(route('inventory.products.update', $product->id), productInput(['name' => 'Ergonomic chair', 'category_id' => $category->id]))
        ->assertRedirect(route('inventory.products.show', $product->id));

    actingInCompany($this->user, $this->company)->get(route('inventory.products.show', $product->id))
        ->assertOk()
        ->assertSee('Ergonomic chair')
        ->assertSee('1300 — Stock')
        ->assertSee('Product updated.');
});

test('product codes are unique per company and accounts must be postable accounts of the right type', function () {
    $otherCompany = Company::factory()->create();
    Product::factory()->create(['company_id' => $otherCompany->id, 'sku' => 'CHAIR-01']);
    $revenue = Account::factory()->revenue()->create(['company_id' => $this->company->id]);
    $otherCompanyAsset = Account::factory()->asset()->create(['company_id' => $otherCompany->id]);

    actingInCompany($this->user, $this->company)->post(route('inventory.products.store'), productInput())->assertSessionHasNoErrors();
    actingInCompany($this->user, $this->company)->post(route('inventory.products.store'), productInput(['name' => 'Duplicate']))->assertSessionHasErrors('sku');
    actingInCompany($this->user, $this->company)->post(route('inventory.products.store'), productInput(['sku' => 'DESK-01', 'inventory_account_id' => $revenue->id]))->assertSessionHasErrors('inventory_account_id');
    actingInCompany($this->user, $this->company)->post(route('inventory.products.store'), productInput(['sku' => 'DESK-01', 'inventory_account_id' => $otherCompanyAsset->id]))->assertSessionHasErrors('inventory_account_id');
    actingInCompany($this->user, $this->company)->post(route('inventory.products.store'), productInput(['sku' => 'DESK-01', 'unit_id' => Unit::withoutGlobalScopes()->where('company_id', $otherCompany->id)->value('id')]))->assertSessionHasErrors('unit_id');
});

test('the product list is searched and filtered by type and reorder level', function () {
    Product::factory()->create(['company_id' => $this->company->id, 'sku' => 'CHAIR-01', 'name' => 'Office chair', 'reorder_level' => 10]);
    Product::factory()->create(['company_id' => $this->company->id, 'sku' => 'DESK-01', 'name' => 'Standing desk']);
    Product::factory()->service()->create(['company_id' => $this->company->id, 'sku' => 'INSTALL', 'name' => 'Installation']);

    actingInCompany($this->user, $this->company)->get(route('inventory.products.index', ['q' => 'chair']))->assertOk()->assertSee('CHAIR-01')->assertDontSee('DESK-01');
    actingInCompany($this->user, $this->company)->get(route('inventory.products.index', ['type' => 'service']))->assertOk()->assertSee('INSTALL')->assertDontSee('CHAIR-01');
    actingInCompany($this->user, $this->company)->get(route('inventory.products.index', ['reorder' => 1]))->assertOk()->assertSee('CHAIR-01')->assertDontSee('DESK-01');
});

test('a product with stock cannot be deleted or change type', function () {
    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $product->forceFill(['stock_quantity' => 3, 'stock_value' => 30])->save();
    $unused = Product::factory()->create(['company_id' => $this->company->id]);

    actingInCompany($this->user, $this->company)->delete(route('inventory.products.destroy', $product->id))->assertSessionHas('error');
    actingInCompany($this->user, $this->company)->put(route('inventory.products.update', $product->id), productInput(['sku' => $product->sku, 'type' => 'service']))->assertSessionHasErrors('type');
    actingInCompany($this->user, $this->company)->delete(route('inventory.products.destroy', $unused->id))->assertRedirect(route('inventory.products.index'));

    expect(Product::withoutGlobalScopes()->pluck('id')->all())->toBe([$product->id]);
});

test('units and categories in use by products cannot be deleted', function () {
    $category = ProductCategory::withoutGlobalScopes()->create(['company_id' => $this->company->id, 'code' => 'FURN', 'name' => 'Furniture', 'status' => 'active']);
    Product::factory()->create(['company_id' => $this->company->id, 'category_id' => $category->id, 'unit_id' => $this->units['BOX']->id]);

    actingInCompany($this->user, $this->company)->delete(route('inventory.units.destroy', $this->units['BOX']->id))->assertSessionHas('error');
    actingInCompany($this->user, $this->company)->delete(route('inventory.categories.destroy', $category->id))->assertSessionHas('error');
    actingInCompany($this->user, $this->company)->delete(route('inventory.units.destroy', $this->units['L']->id))->assertSessionHas('success');

    expect(Unit::withoutGlobalScopes()->where('company_id', $this->company->id)->pluck('code')->sort()->values()->all())->toBe(['BOX', 'EA', 'HR', 'KG', 'M']);
});

test('one warehouse is the default at a time and the default cannot be removed', function () {
    $main = Warehouse::withoutGlobalScopes()->where('company_id', $this->company->id)->sole();

    actingInCompany($this->user, $this->company)->post(route('inventory.warehouses.store'), ['code' => 'WEST', 'name' => 'West depot', 'is_default' => 1, 'status' => 'active'])
        ->assertSessionHasNoErrors();
    $west = Warehouse::withoutGlobalScopes()->where('code', 'WEST')->sole();

    expect($main->fresh()->is_default)->toBeFalse()->and($west->is_default)->toBeTrue();

    actingInCompany($this->user, $this->company)->put(route('inventory.warehouses.update', $west->id), ['code' => 'WEST', 'name' => 'West depot', 'status' => 'inactive'])->assertSessionHas('error');
    actingInCompany($this->user, $this->company)->delete(route('inventory.warehouses.destroy', $west->id))->assertSessionHas('error');
    actingInCompany($this->user, $this->company)->delete(route('inventory.warehouses.destroy', $main->id))->assertSessionHas('success');
});

test('the setup screens list the company\'s units, categories and warehouses', function () {
    ProductCategory::withoutGlobalScopes()->create(['company_id' => $this->company->id, 'code' => 'FURN', 'name' => 'Furniture', 'status' => 'active']);

    actingInCompany($this->user, $this->company)->get(route('inventory.units.index'))->assertOk()->assertSee('Kilogram');
    actingInCompany($this->user, $this->company)->get(route('inventory.categories.index'))->assertOk()->assertSee('Furniture');
    actingInCompany($this->user, $this->company)->get(route('inventory.warehouses.index'))->assertOk()->assertSee('Main warehouse');
    actingInCompany($this->user, $this->company)->get(route('inventory.products.create'))->assertOk()->assertSee('Each');
});

test('products are imported from a spreadsheet by SKU', function () {
    Storage::fake('local');
    Product::factory()->create(['company_id' => $this->company->id, 'sku' => 'CHAIR-01', 'name' => 'Old name']);
    $user = companyUser(['finance.data-import.use', 'inventory.products.manage', 'inventory.products.view'], $this->company);
    $file = UploadedFile::fake()->createWithContent('products.csv', implode("\n", [
        'sku,name,type,unit_code,sales_price,reorder_level',
        'CHAIR-01,Office chair,stock,ea,149,10',
        'INSTALL,Installation,service,HR,60,',
    ]));

    $preview = actingInCompany($user, $this->company)->post(route('finance.imports.preview.products'), ['file' => $file]);
    $preview->assertOk()->assertSeeText('1 to create');
    preg_match('#/imports/products/([0-9a-f-]{36}\.csv)#', $preview->getContent(), $match);

    actingInCompany($user, $this->company)->post(route('finance.imports.confirm.products', $match[1]))->assertRedirect(route('inventory.products.index'));

    $products = Product::withoutGlobalScopes()->get()->keyBy('sku');
    expect($products)->toHaveCount(2)
        ->and($products['CHAIR-01'])->name->toBe('Office chair')->reorder_level->toBe('10.0000')
        ->and($products['INSTALL'])->type->toBe('service')->unit_id->toBe($this->units['HR']->id);
});

test('product rows with unknown units or types are rejected', function () {
    Storage::fake('local');
    $user = companyUser(['finance.data-import.use', 'inventory.products.manage'], $this->company);
    $file = UploadedFile::fake()->createWithContent('products.csv', implode("\n", [
        'sku,name,type,unit_code',
        'A-1,Widget,gadget,EA',
        'A-2,Gizmo,stock,PALLET',
    ]));

    actingInCompany($user, $this->company)->post(route('finance.imports.preview.products'), ['file' => $file])
        ->assertOk()
        ->assertSee('Type must be stock, non_stock or service.')
        ->assertSee('Unit PALLET does not exist.');
});
