<?php

namespace Database\Factories;

use Modules\Finance\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        $accountTypes = ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE'];

        return [
            'company_id' => \Modules\Core\Models\Company::first()?->id ?? 1,
            'parent_id' => null,
            'account_code' => $this->faker->unique()->numerify('####'),
            'account_name' => $this->faker->words(3, true),
            'account_type' => $this->faker->randomElement($accountTypes),
            'account_category' => null,
            'normal_balance' => 'DEBIT',
            'level' => 1,
            'is_group' => false,
            'is_postable' => true,
            'currency_id' => null,
            'status' => 'active',
            'description' => $this->faker->sentence(),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function asset(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'ASSET',
            'normal_balance' => 'DEBIT',
        ]);
    }

    public function liability(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'LIABILITY',
            'normal_balance' => 'CREDIT',
        ]);
    }

    public function equity(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'EQUITY',
            'normal_balance' => 'CREDIT',
        ]);
    }

    public function revenue(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'REVENUE',
            'normal_balance' => 'CREDIT',
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'EXPENSE',
            'normal_balance' => 'DEBIT',
        ]);
    }

    public function group(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_group' => true,
            'is_postable' => false,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
