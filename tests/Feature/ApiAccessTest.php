<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Models\Company;
use Modules\Core\Models\UserCompany;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Journal;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->otherCompany = Company::factory()->create();
    $this->user = companyUser(['finance.journals.view', 'finance.journals.create'], $this->company);
});

/**
 * @param  array<int, string>  $abilities
 */
function companyToken(Company $company, array $abilities = ['*']): string
{
    return test()->user->createToken('test', ['company:'.$company->id, ...$abilities])->plainTextToken;
}

test('a token is issued for a company the user can access', function () {
    $response = $this->postJson('/api/v1/tokens', [
        'email' => $this->user->email,
        'password' => 'password',
        'company_id' => $this->company->id,
        'device_name' => 'integration',
    ]);

    $response->assertCreated()->assertJsonPath('data.company_id', $this->company->id);
    expect($this->user->tokens()->first()->abilities)->toBe(['company:'.$this->company->id, '*']);
});

test('a token is refused for a company the user cannot access', function () {
    $this->postJson('/api/v1/tokens', [
        'email' => $this->user->email,
        'password' => 'password',
        'company_id' => $this->otherCompany->id,
        'device_name' => 'integration',
    ])->assertUnprocessable()->assertJsonValidationErrors(['company_id' => 'You do not have access to this company.']);

    expect($this->user->tokens()->count())->toBe(0);
});

test('a token cannot carry permissions the user does not hold', function () {
    $this->postJson('/api/v1/tokens', [
        'email' => $this->user->email,
        'password' => 'password',
        'company_id' => $this->company->id,
        'device_name' => 'integration',
        'abilities' => ['finance.journals.post'],
    ])->assertUnprocessable()->assertJsonValidationErrors('abilities');
});

test('a token is refused for wrong credentials', function () {
    $this->postJson('/api/v1/tokens', [
        'email' => $this->user->email,
        'password' => 'wrong-password',
        'company_id' => $this->company->id,
        'device_name' => 'integration',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

test('api requests without a token are unauthenticated', function () {
    $this->getJson('/api/v1/finance/journals')->assertUnauthorized();
});

test('a token only sees journals of its own company', function () {
    $ownJournal = Journal::factory()->create(['company_id' => $this->company->id]);
    $foreignJournal = Journal::factory()->create(['company_id' => $this->otherCompany->id]);
    $token = companyToken($this->company);

    $this->withToken($token)->getJson('/api/v1/finance/journals?company_id='.$this->otherCompany->id)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownJournal->id);

    $this->withToken($token)->getJson('/api/v1/finance/journals/'.$foreignJournal->id)->assertNotFound();
});

test('a journal created through the api belongs to the token company', function () {
    $cash = Account::factory()->asset()->create(['company_id' => $this->company->id]);
    $revenue = Account::factory()->revenue()->create(['company_id' => $this->company->id]);

    $this->withToken(companyToken($this->company))->postJson('/api/v1/finance/journals', [
        'company_id' => $this->otherCompany->id,
        'journal_date' => now()->toDateString(),
        'lines' => [
            ['account_id' => $cash->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 100],
        ],
    ])->assertCreated();

    expect(Journal::withoutGlobalScopes()->sole()->company_id)->toBe($this->company->id);
});

test('a token limited to viewing cannot create journals', function () {
    $this->withToken(companyToken($this->company, ['finance.journals.view']))
        ->postJson('/api/v1/finance/journals', [])
        ->assertForbidden();
});

test('a token cannot exceed the permissions of the user roles', function () {
    $this->withToken(companyToken($this->company))
        ->postJson('/api/v1/finance/journals/1/post')
        ->assertForbidden();
});

test('a token stops working when the user loses access to its company', function () {
    $token = companyToken($this->company);
    UserCompany::where('user_id', $this->user->id)->update(['status' => 'inactive']);

    $this->withToken($token)->getJson('/api/v1/finance/journals')->assertForbidden();
});

test('every authenticated api route requires a permission', function () {
    $routesWithoutPermission = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => in_array('auth:sanctum', $route->gatherMiddleware(), true))
        ->reject(fn ($route) => in_array($route->uri(), ['api/v1/tokens/current', 'api/v1/finance/branches'], true))
        ->reject(fn ($route) => collect($route->gatherMiddleware())->contains(fn ($middleware) => str_starts_with($middleware, 'permission:')))
        ->map(fn ($route) => $route->methods()[0].' '.$route->uri())
        ->values();

    expect($routesWithoutPermission)->toBeEmpty();
});
