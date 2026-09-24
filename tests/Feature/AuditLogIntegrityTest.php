<?php

use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Company;
use Modules\Core\Services\AuditService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->audit = app(AuditService::class);
});

function recordEntries(int $count, int $companyId): array
{
    return collect(range(1, $count))
        ->map(fn (int $number) => test()->audit->log('Finance', 'Journal', $number, 'POST', null, ['amount' => "{$number}00.0000", 'lines' => ['b' => 2, 'a' => 1]], $companyId))
        ->all();
}

test('each company audit entry is chained to the previous one', function () {
    [$first, $second] = recordEntries(2, $this->company->id);
    $otherCompanyEntry = $this->audit->log('Finance', 'Journal', 9, 'POST', null, null, Company::factory()->create()->id);

    expect($first->previous_hash)->toBeNull()
        ->and($second->previous_hash)->toBe($first->hash)
        ->and($otherCompanyEntry->previous_hash)->toBeNull()
        ->and($second->fresh()->computeHash())->toBe($second->hash);
});

test('audit entries cannot be changed or deleted through the application', function (string $operation) {
    [$entry] = recordEntries(1, $this->company->id);

    $operation === 'update' ? $entry->update(['action' => 'DELETE']) : $entry->delete();
})->with(['update', 'delete'])->throws(LogicException::class);

test('verification passes for an intact log', function () {
    recordEntries(3, $this->company->id);

    $this->artisan('audit:verify')->expectsOutput('Audit log hash chains are intact.')->assertSuccessful();
});

test('verification detects entries altered or removed directly in the database', function (string $tampering) {
    $entries = recordEntries(3, $this->company->id);
    $row = DB::table('audit_logs')->where('id', $entries[1]->id);

    $tampering === 'altered' ? $row->update(['new_values' => json_encode(['amount' => '1.0000'])]) : $row->delete();

    $this->artisan('audit:verify')->expectsOutputToContain('tampering detected')->assertFailed();
})->with(['altered', 'removed']);
