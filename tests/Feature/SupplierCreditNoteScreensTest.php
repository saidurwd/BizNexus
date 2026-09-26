<?php

use Modules\Core\Models\Company;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\SupplierCreditNote;

test('draft supplier credit notes can be listed and opened for editing', function () {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->create(['company_id' => $company->id]);
    $note = SupplierCreditNote::create([
        'company_id' => $company->id, 'supplier_id' => $supplier->id, 'credit_note_number' => 'SCN-0001',
        'credit_note_date' => '2026-06-01', 'subtotal' => 100, 'tax_amount' => 0, 'total_amount' => 100,
        'reason' => 'Damaged goods', 'status' => SupplierCreditNote::STATUS_DRAFT,
    ]);
    $user = companyUser(['finance.supplier-credit-notes.view', 'finance.supplier-credit-notes.update'], $company);

    actingInCompany($user, $company)->get(route('finance.supplier-credit-notes.index'))->assertOk()->assertSee('SCN-0001');
    actingInCompany($user, $company)->get(route('finance.supplier-credit-notes.edit', $note->id))->assertOk()->assertSee('Damaged goods');
});
