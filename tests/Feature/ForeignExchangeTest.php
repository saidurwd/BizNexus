<?php

use Illuminate\Support\Carbon;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Enums\ExchangeRateType;
use Modules\Finance\Exceptions\MissingExchangeRateException;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\AccountMapping;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Services\CustomerInvoiceService;
use Modules\Finance\Services\ExchangeRateService;
use Modules\Finance\Services\FxRevaluationService;
use Modules\Finance\Services\JournalService;
use Modules\Finance\Services\PaymentService;
use Modules\Finance\Services\ReceiptService;
use Modules\Finance\Services\SupplierInvoiceService;

beforeEach(function () {
    $bdt = Currency::create(['code' => 'BDT', 'name' => 'Taka', 'symbol' => '৳', 'decimal_places' => 2, 'status' => 'active']);
    $this->usd = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'status' => 'active']);
    $this->company = Company::factory()->create(['base_currency_id' => $bdt->id]);
    app(CompanyContextService::class)->pinCompany($this->company->id);

    $fiscalYear = FiscalYear::create(['company_id' => $this->company->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'OPEN']);
    FiscalPeriod::create(['fiscal_year_id' => $fiscalYear->id, 'period_name' => '2026', 'period_number' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'OPEN']);

    $account = fn (string $type, bool $revalue = false) => Account::factory()->{$type}()->create(['company_id' => $this->company->id, 'revalue_foreign_currency' => $revalue]);
    $this->bank = $account('asset', true);
    $this->payable = $account('liability', true);
    $this->revenue = $account('revenue');
    $this->expense = $account('expense');

    $this->mapped = [];
    foreach (['unrealized_fx_gain' => 'revenue', 'unrealized_fx_loss' => 'expense', 'realized_fx_gain' => 'revenue', 'realized_fx_loss' => 'expense', 'fx_rounding' => 'expense', 'cash' => 'asset'] as $purpose => $type) {
        $this->mapped[$purpose] = $account($type)->id;
        AccountMapping::create(['company_id' => $this->company->id, 'purpose' => AccountPurpose::from($purpose), 'account_id' => $this->mapped[$purpose]]);
    }

    $this->rates = app(ExchangeRateService::class);
});

function postUsdJournal(int $debitAccountId, int $creditAccountId, string $amount, string $rate, string $date = '2026-09-10'): Journal
{
    return app(JournalService::class)->postFromSource([
        'journal_date' => $date,
        'currency_id' => test()->usd->id,
        'exchange_rate' => $rate,
        'lines' => [
            ['account_id' => $debitAccountId, 'debit' => $amount],
            ['account_id' => $creditAccountId, 'credit' => $amount],
        ],
    ]);
}

function functionalBalance(int $accountId): string
{
    return (string) JournalLine::where('account_id', $accountId)->whereHas('journal', fn ($query) => $query->posted())
        ->get()->reduce(fn ($total, $line) => bcadd($total, bcsub($line->debit, $line->credit, 4), 4), '0');
}

test('a foreign-currency asset is restated at the closing rate with an unrealised gain', function () {
    postUsdJournal($this->bank->id, $this->revenue->id, '1000', '100');
    $this->rates->record($this->company, $this->usd, Carbon::parse('2026-09-30'), '110', ExchangeRateType::Closing);

    $revaluation = app(FxRevaluationService::class)->revalue($this->company, Carbon::parse('2026-09-30'));

    $lines = $revaluation->journal->lines;
    expect($revaluation->net_gain_loss)->toEqual('10000.0000')
        ->and($lines->firstWhere('account_id', $this->bank->id)->debit)->toEqual('10000.0000')
        ->and($lines->firstWhere('account_id', $this->mapped['unrealized_fx_gain'])->credit)->toEqual('10000.0000')
        ->and($lines->firstWhere('account_id', $this->revenue->id))->toBeNull();
});

test('the revaluation reverses automatically on the next day', function () {
    postUsdJournal($this->bank->id, $this->revenue->id, '1000', '100');
    $this->rates->record($this->company, $this->usd, Carbon::parse('2026-09-30'), '110', ExchangeRateType::Closing);

    $revaluation = app(FxRevaluationService::class)->revalue($this->company, Carbon::parse('2026-09-30'));

    expect($revaluation->reversalJournal->journal_date->toDateString())->toBe('2026-10-01')
        ->and($revaluation->reversalJournal->status)->toBe(Journal::STATUS_POSTED)
        ->and(functionalBalance($this->bank->id))->toEqual('100000.0000');
});

test('a foreign-currency liability is restated with an unrealised loss', function () {
    postUsdJournal($this->expense->id, $this->payable->id, '500', '100');
    $this->rates->record($this->company, $this->usd, Carbon::parse('2026-09-30'), '110', ExchangeRateType::Closing);

    $revaluation = app(FxRevaluationService::class)->revalue($this->company, Carbon::parse('2026-09-30'));

    $lines = $revaluation->journal->lines;
    expect($revaluation->net_gain_loss)->toEqual('-5000.0000')
        ->and($lines->firstWhere('account_id', $this->payable->id)->credit)->toEqual('5000.0000')
        ->and($lines->firstWhere('account_id', $this->mapped['unrealized_fx_loss'])->debit)->toEqual('5000.0000');
});

test('a date can only be revalued once', function () {
    $this->rates->record($this->company, $this->usd, Carbon::parse('2026-09-30'), '110', ExchangeRateType::Closing);
    app(FxRevaluationService::class)->revalue($this->company, Carbon::parse('2026-09-30'));

    app(FxRevaluationService::class)->revalue($this->company, Carbon::parse('2026-09-30'));
})->throws(InvalidAccountingTransactionException::class);

test('revaluation requires a closing rate', function () {
    postUsdJournal($this->bank->id, $this->revenue->id, '1000', '100');
    $this->rates->record($this->company, $this->usd, Carbon::parse('2026-09-30'), '110', ExchangeRateType::Spot);

    app(FxRevaluationService::class)->revalue($this->company, Carbon::parse('2026-09-30'));
})->throws(MissingExchangeRateException::class);

test('settling a foreign invoice at a different rate posts a realised difference and clears the payable', function () {
    $supplier = Supplier::factory()->create(['company_id' => $this->company->id, 'payable_account_id' => $this->payable->id]);
    $invoices = app(SupplierInvoiceService::class);
    $payments = app(PaymentService::class);
    $clerk = companyUser([], $this->company);
    $approver = companyUser([], $this->company);

    $this->actingAs($clerk);
    $invoice = $invoices->submitInvoice($invoices->createInvoice([
        'company_id' => $this->company->id, 'supplier_id' => $supplier->id,
        'invoice_date' => '2026-09-01', 'due_date' => '2026-09-30',
        'currency_id' => $this->usd->id, 'exchange_rate' => '100',
        'lines' => [['account_id' => $this->expense->id, 'description' => 'Licences', 'quantity' => 1, 'unit_price' => 1000]],
    ]));
    $payment = $payments->submitPayment($payments->createPayment([
        'company_id' => $this->company->id, 'supplier_id' => $supplier->id,
        'payment_date' => '2026-09-20', 'currency_id' => $this->usd->id, 'exchange_rate' => '105', 'amount' => 1000,
        'allocations' => [['invoice_id' => $invoice->id, 'amount' => 1000]],
    ]));

    $this->actingAs($approver);
    $invoices->postInvoice($invoices->approveInvoice($invoice));
    $payments->postPayment($payments->approvePayment($payment));

    expect(functionalBalance($this->payable->id))->toEqual('0.0000')
        ->and(functionalBalance($this->mapped['realized_fx_loss']))->toEqual('5000.0000');
});

test('collecting a foreign receivable at a lower rate posts a realised loss and clears the receivable', function () {
    $receivable = Account::factory()->asset()->create(['company_id' => $this->company->id]);
    $customer = Customer::factory()->create(['company_id' => $this->company->id, 'receivable_account_id' => $receivable->id]);
    $invoices = app(CustomerInvoiceService::class);
    $receipts = app(ReceiptService::class);
    $clerk = companyUser([], $this->company);
    $approver = companyUser([], $this->company);

    $this->actingAs($clerk);
    $invoice = $invoices->submitInvoice($invoices->createInvoice([
        'company_id' => $this->company->id, 'customer_id' => $customer->id,
        'invoice_date' => '2026-09-01', 'due_date' => '2026-09-30',
        'currency_id' => $this->usd->id, 'exchange_rate' => '100',
        'lines' => [['account_id' => $this->revenue->id, 'description' => 'Services', 'quantity' => 1, 'unit_price' => 1000]],
    ]));
    $receipt = $receipts->submitReceipt($receipts->createReceipt([
        'company_id' => $this->company->id, 'customer_id' => $customer->id,
        'receipt_date' => '2026-09-20', 'currency_id' => $this->usd->id, 'exchange_rate' => '95', 'amount' => 1000,
        'allocations' => [['invoice_id' => $invoice->id, 'amount' => 1000]],
    ]));

    $this->actingAs($approver);
    $invoices->postInvoice($invoices->approveInvoice($invoice));
    $receipts->postReceipt($receipts->approveReceipt($receipt));

    expect(functionalBalance($receivable->id))->toEqual('0.0000')
        ->and(functionalBalance($this->mapped['realized_fx_loss']))->toEqual('5000.0000');
});

test('a fully collected invoice is marked paid', function () {
    $receivable = Account::factory()->asset()->create(['company_id' => $this->company->id]);
    $customer = Customer::factory()->create(['company_id' => $this->company->id, 'receivable_account_id' => $receivable->id]);
    $invoices = app(CustomerInvoiceService::class);
    $receipts = app(ReceiptService::class);
    $this->actingAs(companyUser([], $this->company));
    $invoice = $invoices->submitInvoice($invoices->createInvoice([
        'company_id' => $this->company->id, 'customer_id' => $customer->id, 'invoice_date' => '2026-09-01', 'due_date' => '2026-09-30',
        'lines' => [['account_id' => $this->revenue->id, 'description' => 'Services', 'quantity' => 1, 'unit_price' => 500]],
    ]));
    $receipt = $receipts->submitReceipt($receipts->createReceipt([
        'company_id' => $this->company->id, 'customer_id' => $customer->id, 'receipt_date' => '2026-09-20', 'amount' => 500,
        'allocations' => [['invoice_id' => $invoice->id, 'amount' => 500]],
    ]));
    $this->actingAs(companyUser([], $this->company));
    $invoices->postInvoice($invoices->approveInvoice($invoice));
    $receipts->postReceipt($receipts->approveReceipt($receipt));

    expect($invoice->fresh()->status)->toBe(CustomerInvoice::STATUS_PAID)
        ->and($invoice->fresh()->outstanding_amount)->toEqual('0.0000');
});
