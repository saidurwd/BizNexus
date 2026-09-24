<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Modules\Core\Models\AuditLog;

#[Signature('audit:verify')]
#[Description('Verify the audit log hash chain of every company and report altered or removed entries')]
class VerifyAuditLog extends Command
{
    public function handle(): int
    {
        $broken = [];

        foreach (AuditLog::whereNotNull('hash')->distinct()->pluck('company_id') as $companyId) {
            $expectedPreviousHash = null;

            AuditLog::where('company_id', $companyId)->whereNotNull('hash')->orderBy('id')
                ->chunkById(500, function ($entries) use (&$expectedPreviousHash, &$broken, $companyId) {
                    foreach ($entries as $entry) {
                        if ($entry->previous_hash !== $expectedPreviousHash || $entry->computeHash() !== $entry->hash) {
                            $broken[] = [$companyId ?? '-', $entry->id, $entry->action, $entry->created_at?->toDateTimeString()];

                            return false;
                        }

                        $expectedPreviousHash = $entry->hash;
                    }
                });
        }

        if ($broken === []) {
            $this->info('Audit log hash chains are intact.');

            return self::SUCCESS;
        }

        $this->error('Audit log tampering detected. First broken entry per company:');
        $this->table(['Company', 'Entry', 'Action', 'Recorded at'], $broken);

        return self::FAILURE;
    }
}
