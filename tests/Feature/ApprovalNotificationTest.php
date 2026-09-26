<?php

use Modules\Core\Models\ApprovalDelegation;
use Modules\Core\Models\Company;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Customer;
use Modules\Finance\Services\CustomerInvoiceService;

test('submitting a document notifies its approvers and their delegates, not the submitter', function () {
    $company = Company::factory()->create();
    app(CompanyContextService::class)->pinCompany($company->id);
    $clerk = companyUser(['finance.customer-invoices.approve'], $company);
    $approver = companyUser(['finance.customer-invoices.approve'], $company);
    $delegate = companyUser([], $company);
    $bystander = companyUser(['finance.customer-invoices.view'], $company);
    ApprovalDelegation::create(['company_id' => $company->id, 'delegator_id' => $approver->id, 'delegate_id' => $delegate->id, 'starts_on' => now()->subDay(), 'ends_on' => now()->addWeek()]);

    $this->actingAs($clerk);
    $service = app(CustomerInvoiceService::class);
    $invoice = $service->submitInvoice($service->createInvoice([
        'company_id' => $company->id, 'customer_id' => Customer::factory()->create(['company_id' => $company->id])->id,
        'invoice_date' => now()->toDateString(), 'due_date' => now()->addMonth()->toDateString(),
        'lines' => [['account_id' => Account::factory()->revenue()->create(['company_id' => $company->id])->id, 'description' => 'Service', 'quantity' => 1, 'unit_price' => 100]],
    ]));

    expect($approver->notifications()->sole()->data)->toMatchArray(['type' => 'approval_requested', 'number' => $invoice->invoice_number, 'url' => route('finance.customer-invoices.show', $invoice->id)])
        ->and($delegate->notifications()->count())->toBe(1)
        ->and($clerk->notifications()->count())->toBe(0)
        ->and($bystander->notifications()->count())->toBe(0);

    actingInCompany($approver, $company)->getJson(route('core.notifications.dropdown'))
        ->assertOk()
        ->assertJson(['label' => 1, 'label_color' => 'danger'])
        ->assertJsonPath('dropdown', fn (string $html) => str_contains($html, $invoice->invoice_number) && str_contains($html, '1 unread notification'));

    $notificationId = $approver->notifications()->value('id');
    actingInCompany($approver, $company)->get(route('core.notifications.open', $notificationId))
        ->assertRedirect(route('finance.customer-invoices.show', $invoice->id));
    expect($approver->unreadNotifications()->count())->toBe(0);

    actingInCompany($approver, $company)->get(route('core.notifications.index'))->assertOk()->assertSee($invoice->invoice_number)->assertSee(route('finance.customer-invoices.show', $invoice->id), false);
});
