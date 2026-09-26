<?php

use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Models\Company;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\AccountMapping;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\Tax;
use Modules\Finance\Models\TaxRule;
use Modules\Finance\Models\TaxTransaction;
use Modules\Finance\Services\CustomerInvoiceService;
use Modules\Finance\Services\PaymentService;
use Modules\Finance\Services\SupplierInvoiceService;
use Modules\Finance\Services\TaxReturnService;

beforeEach(function () {
    $this->company = Company::factory()->create(['country_code' => 'NL']);
    app(CompanyContextService::class)->pinCompany($this->company->id);
    $year = FiscalYear::create(['company_id' => $this->company->id, 'name' => 'FY', 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'status' => 'OPEN']);
    FiscalPeriod::create(['fiscal_year_id' => $year->id, 'period_name' => 'Y', 'period_number' => 1, 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'status' => 'OPEN']);

    $account = fn (string $type) => Account::factory()->{$type}()->create(['company_id' => $this->company->id]);
    $this->expense = $account('expense');
    $this->otherExpense = $account('expense');
    $this->revenue = $account('revenue');
    $this->payable = $account('liability');
    $this->receivable = $account('asset');
    $this->inputVat = $account('asset');
    $this->outputVat = $account('liability');

    $this->vat = Tax::factory()->create([
        'company_id' => $this->company->id, 'tax_code' => 'VAT15', 'tax_name' => 'VAT 15%', 'tax_type' => 'VAT', 'rate' => 15,
        'is_inclusive' => false, 'is_recoverable' => true, 'input_account_id' => $this->inputVat->id, 'output_account_id' => $this->outputVat->id,
    ]);
    $this->supplier = Supplier::factory()->create(['company_id' => $this->company->id, 'payable_account_id' => $this->payable->id, 'country_code' => 'NL']);
    $this->customer = Customer::factory()->create(['company_id' => $this->company->id, 'receivable_account_id' => $this->receivable->id]);
    $this->clerk = companyUser([], $this->company);
    $this->approver = companyUser([], $this->company);
});

function postPurchase(array $lines, array $header = []): SupplierInvoice
{
    $service = app(SupplierInvoiceService::class);
    test()->actingAs(test()->clerk);
    $invoice = $service->submitInvoice($service->createInvoice([
        'company_id' => test()->company->id, 'supplier_id' => test()->supplier->id,
        'invoice_date' => now()->toDateString(), 'due_date' => now()->addMonth()->toDateString(),
        'lines' => $lines, ...$header,
    ]));
    test()->actingAs(test()->approver);

    return $service->postInvoice($service->approveInvoice($invoice));
}

function amountOn(int $accountId): string
{
    return (string) JournalLine::where('account_id', $accountId)->get()
        ->reduce(fn ($total, $line) => bcadd($total, bcsub($line->debit, $line->credit, 4), 4), '0');
}

test('a purchase with recoverable VAT books net cost, input VAT and the gross payable', function () {
    $invoice = postPurchase([['account_id' => $this->expense->id, 'description' => 'Laptop', 'quantity' => 1, 'unit_price' => 1000, 'tax_id' => $this->vat->id]]);

    expect($invoice->total_amount)->toEqual('1150.0000')
        ->and(amountOn($this->expense->id))->toEqual('1000.0000')
        ->and(amountOn($this->inputVat->id))->toEqual('150.0000')
        ->and(amountOn($this->payable->id))->toEqual('-1150.0000')
        ->and(TaxTransaction::where('invoice_id', $invoice->id)->where('transaction_type', 'INPUT')->value('tax_amount'))->toEqual('150.0000');
});

test('a header discount is spread over the lines before tax', function () {
    $invoice = postPurchase([
        ['account_id' => $this->expense->id, 'description' => 'A', 'quantity' => 1, 'unit_price' => 600, 'tax_id' => $this->vat->id],
        ['account_id' => $this->otherExpense->id, 'description' => 'B', 'quantity' => 1, 'unit_price' => 400, 'tax_id' => $this->vat->id],
    ], ['discount_amount' => 100]);

    expect($invoice->subtotal)->toEqual('900.0000')
        ->and($invoice->total_amount)->toEqual('1035.0000')
        ->and(amountOn($this->expense->id))->toEqual('540.0000')
        ->and(amountOn($this->otherExpense->id))->toEqual('360.0000');
});

test('a tax-inclusive price is not taxed twice', function () {
    $this->vat->update(['is_inclusive' => true]);

    $invoice = postPurchase([['account_id' => $this->expense->id, 'description' => 'Inclusive', 'quantity' => 1, 'unit_price' => 1150, 'tax_id' => $this->vat->id]]);

    expect($invoice->subtotal)->toEqual('1000.0000')
        ->and($invoice->tax_amount)->toEqual('150.0000')
        ->and($invoice->total_amount)->toEqual('1150.0000');
});

test('non-recoverable tax is added to the cost', function () {
    $this->vat->update(['is_recoverable' => false]);

    postPurchase([['account_id' => $this->expense->id, 'description' => 'Entertainment', 'quantity' => 1, 'unit_price' => 1000, 'tax_id' => $this->vat->id]]);

    expect(amountOn($this->expense->id))->toEqual('1150.0000')
        ->and(amountOn($this->inputVat->id))->toEqual('0');
});

test('a reverse-charge purchase self-assesses input and output VAT and pays only the net', function () {
    $invoice = postPurchase([['account_id' => $this->expense->id, 'description' => 'Cloud services', 'quantity' => 1, 'unit_price' => 1000, 'tax_id' => $this->vat->id, 'is_reverse_charge' => true]]);

    expect($invoice->total_amount)->toEqual('1000.0000')
        ->and(amountOn($this->payable->id))->toEqual('-1000.0000')
        ->and(amountOn($this->inputVat->id))->toEqual('150.0000')
        ->and(amountOn($this->outputVat->id))->toEqual('-150.0000')
        ->and(TaxTransaction::where('invoice_id', $invoice->id)->where('is_reverse_charge', true)->pluck('transaction_type')->sort()->values()->all())->toBe(['INPUT', 'OUTPUT']);
});

test('the tax code and reverse charge come from the determination rules', function () {
    $this->supplier->update(['country_code' => 'DE', 'tax_number' => 'DE123456789']);
    TaxRule::create(['company_id' => $this->company->id, 'direction' => 'purchase', 'counterparty_country' => 'DE', 'counterparty_type' => 'b2b', 'supply_type' => 'services', 'tax_id' => $this->vat->id, 'reverse_charge' => true]);
    TaxRule::create(['company_id' => $this->company->id, 'direction' => 'purchase', 'tax_id' => null, 'priority' => 999]);

    $invoice = postPurchase([['account_id' => $this->expense->id, 'description' => 'Consulting', 'quantity' => 1, 'unit_price' => 1000, 'supply_type' => 'services']]);

    $line = $invoice->lines()->sole();
    expect($line->tax_id)->toBe($this->vat->id)
        ->and($line->is_reverse_charge)->toBeTrue()
        ->and($invoice->total_amount)->toEqual('1000.0000');
});

test('a sales invoice books the gross receivable, net revenue and output VAT', function () {
    $service = app(CustomerInvoiceService::class);
    $this->actingAs($this->clerk);
    $invoice = $service->submitInvoice($service->createInvoice([
        'company_id' => $this->company->id, 'customer_id' => $this->customer->id,
        'invoice_date' => now()->toDateString(), 'due_date' => now()->addMonth()->toDateString(),
        'lines' => [['account_id' => $this->revenue->id, 'description' => 'Subscription', 'quantity' => 2, 'unit_price' => 500, 'tax_id' => $this->vat->id]],
    ]));
    $this->actingAs($this->approver);
    $service->postInvoice($service->approveInvoice($invoice));

    expect(amountOn($this->receivable->id))->toEqual('1150.0000')
        ->and(amountOn($this->revenue->id))->toEqual('-1000.0000')
        ->and(amountOn($this->outputVat->id))->toEqual('-150.0000')
        ->and(TaxTransaction::where('customer_invoice_id', $invoice->id)->value('transaction_type'))->toBe('OUTPUT');
});

test('posting fails clearly when a tax code has no input tax account', function () {
    $this->vat->update(['input_account_id' => null]);

    postPurchase([['account_id' => $this->expense->id, 'description' => 'Laptop', 'quantity' => 1, 'unit_price' => 1000, 'tax_id' => $this->vat->id]]);
})->throws(InvalidAccountingTransactionException::class, 'Tax VAT15 has no input tax account.');

test('a supplier country is chosen from ISO 3166 codes', function () {
    $user = companyUser(['finance.suppliers.create', 'finance.suppliers.update'], $this->company);

    actingInCompany($user, $this->company)->get(route('finance.suppliers.create'))->assertOk()->assertSee('Germany (DE)');
    actingInCompany($user, $this->company)
        ->put(route('finance.suppliers.update', $this->supplier->id), ['supplier_code' => $this->supplier->supplier_code, 'name' => $this->supplier->name, 'country_code' => 'XX', 'status' => 'active'])
        ->assertSessionHasErrors('country_code');
});

test('withholding tax is deducted from the payment and owed to the tax authority', function () {
    $whtPayable = Account::factory()->liability()->create(['company_id' => $this->company->id]);
    $bank = Account::factory()->asset()->create(['company_id' => $this->company->id]);
    AccountMapping::create(['company_id' => $this->company->id, 'purpose' => AccountPurpose::Cash, 'account_id' => $bank->id]);
    $wht = Tax::factory()->create(['company_id' => $this->company->id, 'tax_code' => 'WHT10', 'tax_type' => 'WITHHOLDING_TAX', 'rate' => 10, 'output_account_id' => $whtPayable->id]);
    $invoice = postPurchase([['account_id' => $this->expense->id, 'description' => 'Consulting', 'quantity' => 1, 'unit_price' => 1000]]);
    $payments = app(PaymentService::class);

    $this->actingAs($this->clerk);
    $payment = $payments->submitPayment($payments->createPayment([
        'company_id' => $this->company->id, 'supplier_id' => $this->supplier->id, 'payment_date' => now()->toDateString(),
        'amount' => 1000, 'payment_method' => 'CASH', 'withholding_tax_id' => $wht->id,
        'allocations' => [['invoice_id' => $invoice->id, 'amount' => 1000]],
    ]));
    $this->actingAs($this->approver);
    $payments->postPayment($payments->approvePayment($payment));

    expect($payment->fresh()->withholding_amount)->toEqual('100.0000')
        ->and(amountOn($this->payable->id))->toEqual('0.0000')
        ->and(amountOn($bank->id))->toEqual('-900.0000')
        ->and(amountOn($whtPayable->id))->toEqual('-100.0000')
        ->and(TaxTransaction::where('payment_id', $payment->id)->value('transaction_type'))->toBe('WITHHOLDING')
        ->and($invoice->fresh()->status)->toBe(SupplierInvoice::STATUS_PAID);
});

test('only withholding taxes can be withheld from payments', function () {
    app(PaymentService::class)->createPayment([
        'company_id' => $this->company->id, 'supplier_id' => $this->supplier->id, 'payment_date' => now()->toDateString(),
        'amount' => 100, 'withholding_tax_id' => $this->vat->id,
    ]);
})->throws(InvalidAccountingTransactionException::class, 'is not a withholding tax');

test('the tax return nets output and reverse-charge tax against recoverable input tax', function () {
    $sales = app(CustomerInvoiceService::class);
    $this->actingAs($this->clerk);
    $sale = $sales->submitInvoice($sales->createInvoice([
        'company_id' => $this->company->id, 'customer_id' => $this->customer->id,
        'invoice_date' => now()->toDateString(), 'due_date' => now()->addMonth()->toDateString(),
        'lines' => [['account_id' => $this->revenue->id, 'description' => 'Goods', 'quantity' => 1, 'unit_price' => 2000, 'tax_id' => $this->vat->id]],
    ]));
    $this->actingAs($this->approver);
    $sales->postInvoice($sales->approveInvoice($sale));
    postPurchase([['account_id' => $this->expense->id, 'description' => 'Local purchase', 'quantity' => 1, 'unit_price' => 1000, 'tax_id' => $this->vat->id]]);
    postPurchase([['account_id' => $this->expense->id, 'description' => 'Foreign services', 'quantity' => 1, 'unit_price' => 1000, 'tax_id' => $this->vat->id, 'is_reverse_charge' => true]]);

    $summary = app(TaxReturnService::class)->summarise($this->company, now()->startOfMonth(), now()->endOfMonth());

    expect($summary['totals']['output']->amount)->toBe('300.00')
        ->and($summary['totals']['reverse_charge_output']->amount)->toBe('150.00')
        ->and($summary['totals']['input']->amount)->toBe('300.00')
        ->and($summary['net_payable']->amount)->toBe('150.00');

    $user = companyUser(['finance.reports.view'], $this->company);
    actingInCompany($user, $this->company)
        ->get(route('finance.tax-return', ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->endOfMonth()->toDateString()]))
        ->assertOk()->assertSee('Net payable')->assertSee('150.00');
    actingInCompany($user, $this->company)
        ->get(route('finance.tax-return', ['format' => 'csv', 'from' => now()->startOfMonth()->toDateString(), 'to' => now()->endOfMonth()->toDateString()]))
        ->assertOk()->assertDownload();
});

test('a posted sales invoice is issued as a Peppol BIS 3 UBL invoice', function () {
    $this->company->update(['tax_number' => 'NL123456789B01', 'email' => 'billing@example.com']);
    $this->customer->update(['country_code' => 'DE', 'tax_number' => 'DE987654321']);
    $sales = app(CustomerInvoiceService::class);
    $this->actingAs($this->clerk);
    $invoice = $sales->submitInvoice($sales->createInvoice([
        'company_id' => $this->company->id, 'customer_id' => $this->customer->id,
        'invoice_date' => now()->toDateString(), 'due_date' => now()->addMonth()->toDateString(),
        'lines' => [
            ['account_id' => $this->revenue->id, 'description' => 'Licence', 'quantity' => 2, 'unit_price' => 500, 'tax_id' => $this->vat->id],
            ['account_id' => $this->revenue->id, 'description' => 'Consulting (reverse charge)', 'quantity' => 1, 'unit_price' => 300, 'tax_id' => $this->vat->id, 'is_reverse_charge' => true],
        ],
    ]));
    $this->actingAs($this->approver);
    $sales->postInvoice($sales->approveInvoice($invoice));

    $response = actingInCompany(companyUser(['finance.customer-invoices.view'], $this->company), $this->company)
        ->get(route('finance.customer-invoices.e-invoice', $invoice->id))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml');

    $xml = simplexml_load_string($response->getContent());
    $xml->registerXPathNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
    $xml->registerXPathNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
    $value = fn (string $path) => (string) ($xml->xpath($path)[0] ?? '');

    expect($value('/*/cbc:CustomizationID'))->toBe('urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0')
        ->and($value('/*/cbc:ID'))->toBe($invoice->invoice_number)
        ->and($value('//cac:AccountingSupplierParty//cac:PartyTaxScheme/cbc:CompanyID'))->toBe('NL123456789B01')
        ->and($value('//cac:AccountingCustomerParty//cac:Country/cbc:IdentificationCode'))->toBe('DE')
        ->and($value("//cac:TaxTotal/cac:TaxSubtotal[cac:TaxCategory/cbc:ID='S']/cbc:TaxAmount"))->toBe('150.00')
        ->and($value("//cac:TaxTotal/cac:TaxSubtotal[cac:TaxCategory/cbc:ID='S']/cac:TaxCategory/cbc:Percent"))->toBe('15.00')
        ->and($value("//cac:TaxTotal/cac:TaxSubtotal[cac:TaxCategory/cbc:ID='AE']/cbc:TaxableAmount"))->toBe('300.00')
        ->and($value('//cac:LegalMonetaryTotal/cbc:PayableAmount'))->toBe('1450.00')
        ->and(count($xml->xpath('//cac:InvoiceLine')))->toBe(2);
});

test('only posted invoices are issued electronically', function () {
    $sales = app(CustomerInvoiceService::class);
    $invoice = $sales->createInvoice([
        'company_id' => $this->company->id, 'customer_id' => $this->customer->id,
        'invoice_date' => now()->toDateString(), 'due_date' => now()->addMonth()->toDateString(),
        'lines' => [['account_id' => $this->revenue->id, 'description' => 'Draft', 'quantity' => 1, 'unit_price' => 10]],
    ]);

    actingInCompany(companyUser(['finance.customer-invoices.view'], $this->company), $this->company)
        ->get(route('finance.customer-invoices.e-invoice', $invoice->id))
        ->assertStatus(422);
});
