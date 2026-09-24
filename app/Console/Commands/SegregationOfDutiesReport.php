<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Modules\Core\Models\Company;
use Modules\Core\Services\SegregationOfDutiesService;

#[Signature('authorization:sod-report')]
#[Description('List users whose roles in a company combine conflicting permissions')]
class SegregationOfDutiesReport extends Command
{
    public function handle(SegregationOfDutiesService $sod): int
    {
        $violations = $sod->existingViolations();

        if ($violations->isEmpty()) {
            $this->info('No segregation of duties conflicts found.');

            return self::SUCCESS;
        }

        $users = User::whereIn('id', $violations->pluck('user_id'))->pluck('email', 'id');
        $companies = Company::whereIn('id', $violations->pluck('company_id'))->pluck('code', 'id');

        $this->table(['User', 'Company', 'Conflicting permissions'], $violations->map(fn (array $violation) => [
            $users[$violation['user_id']] ?? $violation['user_id'],
            $companies[$violation['company_id']] ?? $violation['company_id'],
            $sod->describe($violation['conflicts']),
        ]));

        return self::FAILURE;
    }
}
