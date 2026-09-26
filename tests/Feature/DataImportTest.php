<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Models\Company;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\PaymentTerm;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

beforeEach(function () {
    Storage::fake('local');
    $this->company = Company::factory()->create();
    $this->user = companyUser(['finance.data-import.use', 'finance.accounts.create', 'finance.customers.create', 'finance.journals.create'], $this->company);
});

function csvFile(string $name, array $rows): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, implode("\n", array_map(fn (array $row) => implode(',', array_map(fn ($value) => str_contains((string) $value, ',') ? '"'.$value.'"' : $value, $row)), $rows)));
}

/**
 * Upload the file, then confirm the token from the preview when it passed.
 */
function importFile(string $type, UploadedFile $file, array $extra = [])
{
    $preview = actingInCompany(test()->user, test()->company)->post(route('finance.imports.preview.'.$type), ['file' => $file, ...$extra]);
    preg_match('#/imports/'.$type.'/([0-9a-f-]{36}\.(csv|xlsx))#', $preview->getContent(), $match);

    return [$preview, $match[1] ?? null];
}

test('a chart of accounts is imported with parents created first and made heading accounts', function () {
    [$preview, $token] = importFile('accounts', csvFile('coa.csv', [
        ['account_code', 'account_name', 'account_type', 'parent_code', 'cash_flow_category'],
        ['1100', 'Main bank', 'ASSET', '1000', 'cash'],
        ['1000', 'Current assets', 'ASSET', '', ''],
    ]));
    $preview->assertOk()->assertSeeText('2 to create');

    actingInCompany($this->user, $this->company)->post(route('finance.imports.confirm.accounts', $token))->assertRedirect(route('finance.accounts.index'));

    $accounts = Account::withoutGlobalScopes()->where('company_id', $this->company->id)->get()->keyBy('account_code');
    expect($accounts['1000'])->is_group->toBeTrue()->is_postable->toBeFalse()->level->toBe(1)
        ->and($accounts['1100'])->is_postable->toBeTrue()->level->toBe(2)->parent_id->toBe($accounts['1000']->id)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('rows with problems are shown and nothing can be imported', function () {
    Account::factory()->asset()->create(['company_id' => $this->company->id, 'account_code' => '1100']);

    [$preview, $token] = importFile('accounts', csvFile('coa.csv', [
        ['account_code', 'account_name', 'account_type'],
        ['1100', 'Duplicate', 'ASSET'],
        ['4000', 'Sales', 'INCOME'],
    ]));

    $preview->assertOk()->assertSee('Account 1100 already exists.')->assertSee('Account type must be one of')->assertDontSee('Import 2 rows');
    expect($token)->toBeNull();
});

test('customers are created or updated by code', function () {
    Customer::factory()->create(['company_id' => $this->company->id, 'customer_code' => 'C-1', 'name' => 'Old Name']);
    $net30 = PaymentTerm::withoutGlobalScopes()->where('company_id', $this->company->id)->where('code', 'NET30')->value('id');

    [, $token] = importFile('customers', csvFile('customers.csv', [
        ['code', 'name', 'email', 'country_code', 'payment_term_code', 'credit_limit'],
        ['C-1', 'New Name', 'ap@one.test', 'GB', 'NET30', '1000'],
        ['C-2', 'Second Customer', '', 'DE', '', ''],
    ]));

    actingInCompany($this->user, $this->company)->post(route('finance.imports.confirm.customers', $token))->assertSessionHas('success', fn (string $message) => str_contains($message, '1 created, 1 updated'));

    $customers = Customer::withoutGlobalScopes()->where('company_id', $this->company->id)->get()->keyBy('customer_code');
    expect($customers)->toHaveCount(2)
        ->and($customers['C-1'])->name->toBe('New Name')->payment_term_id->toBe($net30)->country_code->toBe('GB')
        ->and((string) $customers['C-1']->credit_limit)->toBe('1000.0000');
});

test('opening balances become one balanced draft journal, and unbalanced files are refused', function () {
    $bank = Account::factory()->asset()->create(['company_id' => $this->company->id, 'account_code' => '1100']);
    Account::factory()->equity()->create(['company_id' => $this->company->id, 'account_code' => '3000']);

    [$unbalanced] = importFile('opening-balances', csvFile('ob.csv', [['account_code', 'debit', 'credit'], ['1100', '1,000.00', ''], ['3000', '', '900']]), ['date' => '2025-12-31']);
    $unbalanced->assertSee('Debits (1000.0000) do not equal credits (900.0000).');

    [, $token] = importFile('opening-balances', csvFile('ob.csv', [['account_code', 'debit', 'credit', 'description'], ['1100', '1,000.00', '', 'Bank'], ['3000', '', '1000', 'Equity']]), ['date' => '2025-12-31']);
    actingInCompany($this->user, $this->company)->post(route('finance.imports.confirm.opening-balances', $token), ['date' => '2025-12-31'])->assertRedirect(route('finance.journals.index'));

    $journal = Journal::withoutGlobalScopes()->with('lines')->sole();
    expect($journal->status)->toBe(Journal::STATUS_DRAFT)
        ->and($journal->journal_date->toDateString())->toBe('2025-12-31')
        ->and((string) $journal->lines->firstWhere('account_id', $bank->id)->debit)->toBe('1000.0000');
});

test('Excel files are read like CSV files', function () {
    $path = tempnam(sys_get_temp_dir(), 'import').'.xlsx';
    $writer = new Writer;
    $writer->openToFile($path);
    $writer->addRow(Row::fromValues(['Account Code', 'Account Name', 'Account Type']));
    $writer->addRow(Row::fromValues(['5000', 'Rent', 'EXPENSE']));
    $writer->close();

    [, $token] = importFile('accounts', new UploadedFile($path, 'coa.xlsx', null, null, true));
    actingInCompany($this->user, $this->company)->post(route('finance.imports.confirm.accounts', $token));

    expect(Account::withoutGlobalScopes()->where('company_id', $this->company->id)->where('account_code', '5000')->exists())->toBeTrue();
});

test('templates download and each import needs the data type\'s create permission', function () {
    expect(actingInCompany($this->user, $this->company)->get(route('finance.imports.template.customers'))->assertOk()->streamedContent())
        ->toContain('code,name,contact_person')->toContain('Globex Ltd');
    actingInCompany($this->user, $this->company)->get(route('finance.imports.index'))->assertOk()->assertSee('Chart of accounts')->assertDontSee('Suppliers');

    actingInCompany($this->user, $this->company)
        ->post(route('finance.imports.preview.suppliers'), ['file' => csvFile('s.csv', [['code', 'name'], ['S-1', 'X']])])
        ->assertForbidden();
});
