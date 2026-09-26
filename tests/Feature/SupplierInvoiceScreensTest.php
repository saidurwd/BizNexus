<?php

use Modules\Core\Models\Company;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\Tax;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->supplier = Supplier::factory()->create(['company_id' => $this->company->id]);
    $this->expense = Account::factory()->expense()->create(['company_id' => $this->company->id]);
    $this->vat = Tax::factory()->create(['company_id' => $this->company->id, 'tax_code' => 'VAT15', 'rate' => 15, 'is_inclusive' => false, 'is_group' => false, 'is_recoverable' => true]);
    $this->user = companyUser(['finance.supplier-invoices.view', 'finance.supplier-invoices.create', 'finance.supplier-invoices.update'], $this->company);
});

function supplierInvoiceForm(array $overrides = []): array
{
    return [
        'supplier_id' => test()->supplier->id,
        'invoice_number' => 'INV-001',
        'invoice_date' => '2026-06-01',
        'due_date' => '2026-07-01',
        'lines' => [['account_id' => test()->expense->id, 'description' => 'Office chairs', 'quantity' => 4, 'unit_price' => 50, 'tax_id' => test()->vat->id]],
        ...$overrides,
    ];
}

test('a supplier invoice is recorded with its lines and tax', function () {
    actingInCompany($this->user, $this->company)
        ->post(route('finance.supplier-invoices.store'), supplierInvoiceForm())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $invoice = SupplierInvoice::withoutGlobalScopes()->with('lines')->sole();

    expect($invoice->lines)->toHaveCount(1)
        ->and((string) $invoice->total_amount)->toBe('230.0000')
        ->and((string) $invoice->outstanding_amount)->toBe('230.0000');

    actingInCompany($this->user, $this->company)->get(route('finance.supplier-invoices.show', $invoice->id))->assertOk()->assertSee('Office chairs');
    actingInCompany($this->user, $this->company)->get(route('finance.supplier-invoices.create'))->assertOk()->assertSee('data-add-line', false);
});

test('invoice numbers are unique per supplier, not across suppliers', function () {
    $otherSupplier = Supplier::factory()->create(['company_id' => $this->company->id]);

    actingInCompany($this->user, $this->company)->post(route('finance.supplier-invoices.store'), supplierInvoiceForm());
    actingInCompany($this->user, $this->company)->post(route('finance.supplier-invoices.store'), supplierInvoiceForm(['supplier_id' => $otherSupplier->id]))->assertSessionHasNoErrors();
    actingInCompany($this->user, $this->company)->post(route('finance.supplier-invoices.store'), supplierInvoiceForm())->assertSessionHasErrors('invoice_number');

    expect(SupplierInvoice::withoutGlobalScopes()->count())->toBe(2);
});

test('editing a draft supplier invoice replaces its lines', function () {
    actingInCompany($this->user, $this->company)->post(route('finance.supplier-invoices.store'), supplierInvoiceForm());
    $invoice = SupplierInvoice::withoutGlobalScopes()->sole();

    actingInCompany($this->user, $this->company)->get(route('finance.supplier-invoices.edit', $invoice->id))->assertOk()->assertSee('Office chairs');
    actingInCompany($this->user, $this->company)
        ->put(route('finance.supplier-invoices.update', $invoice->id), supplierInvoiceForm(['lines' => [['account_id' => $this->expense->id, 'description' => 'Desk', 'quantity' => 1, 'unit_price' => 300]]]))
        ->assertRedirect(route('finance.supplier-invoices.show', $invoice->id));

    expect($invoice->fresh()->lines->pluck('description')->all())->toBe(['Desk'])
        ->and((string) $invoice->fresh()->total_amount)->toBe('300.0000');
});
