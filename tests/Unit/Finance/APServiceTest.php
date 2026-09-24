<?php

namespace Tests\Unit\Finance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\Company;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Services\PaymentService;
use Modules\Finance\Services\SupplierInvoiceService;
use Tests\TestCase;

class APServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SupplierInvoiceService $supplierInvoiceService;

    protected PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supplierInvoiceService = app(SupplierInvoiceService::class);

        $this->paymentService = app(PaymentService::class);
    }

    public function test_can_create_supplier_invoice(): void
    {
        $company = Company::factory()->create();
        app(CompanyContextService::class)->pinCompany($company->id);
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $expenseAccount = Account::factory()->expense()->create(['company_id' => $company->id]);

        $invoiceData = [
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'lines' => [
                [
                    'account_id' => $expenseAccount->id,
                    'description' => 'Office supplies',
                    'quantity' => 10,
                    'unit_price' => 100,
                ],
            ],
        ];

        $invoice = $this->supplierInvoiceService->createInvoice($invoiceData);

        $this->assertNotNull($invoice);
        $this->assertEquals(SupplierInvoice::STATUS_DRAFT, $invoice->status);
        $this->assertEquals(1000, $invoice->subtotal);
    }

    public function test_supplier_invoice_calculates_outstanding_correctly(): void
    {
        $company = Company::factory()->create();
        app(CompanyContextService::class)->pinCompany($company->id);
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $expenseAccount = Account::factory()->expense()->create(['company_id' => $company->id]);

        $invoiceData = [
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'lines' => [
                [
                    'account_id' => $expenseAccount->id,
                    'description' => 'Services',
                    'quantity' => 1,
                    'unit_price' => 5000,
                ],
            ],
        ];

        $invoice = $this->supplierInvoiceService->createInvoice($invoiceData);

        $this->assertEquals(5000, $invoice->total_amount);
        $this->assertEquals(5000, $invoice->outstanding_amount);
    }

    public function test_ap_aging_groups_invoices_by_age(): void
    {
        $company = Company::factory()->create();
        app(CompanyContextService::class)->pinCompany($company->id);
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $expenseAccount = Account::factory()->expense()->create(['company_id' => $company->id]);

        $invoiceData = [
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'invoice_date' => now()->subDays(90)->format('Y-m-d'),
            'due_date' => now()->subDays(60)->format('Y-m-d'),
            'lines' => [
                [
                    'account_id' => $expenseAccount->id,
                    'description' => 'Overdue invoice',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],
        ];

        $invoice = $this->supplierInvoiceService->createInvoice($invoiceData);

        // Post the invoice to include it in aging
        $invoice->update(['status' => SupplierInvoice::STATUS_POSTED]);

        $aging = $this->paymentService->getAPAging($company->id);

        $this->assertArrayHasKey('days_1_30', $aging);
        $this->assertArrayHasKey('days_31_60', $aging);
        $this->assertGreaterThan(0, $aging['days_31_60']);
    }
}
