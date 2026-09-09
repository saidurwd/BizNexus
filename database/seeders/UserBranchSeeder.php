<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\User;
use Modules\Core\Models\UserBranch;
use Modules\Core\Models\Branch;

class UserBranchSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $companyIds = $user->userCompanies()->where('status', 'active')->pluck('company_id');

            foreach ($companyIds as $companyId) {
                $branches = Branch::where('company_id', $companyId)
                    ->where('status', 'active')
                    ->get();

                foreach ($branches as $branch) {
                    UserBranch::firstOrCreate(
                        [
                            'user_id' => $user->id,
                            'company_id' => $companyId,
                            'branch_id' => $branch->id,
                        ],
                        ['status' => 'active']
                    );
                }
            }
        }
    }
}
