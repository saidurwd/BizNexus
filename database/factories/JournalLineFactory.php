<?php

namespace Database\Factories;

use Modules\Finance\Models\JournalLine;
use Modules\Finance\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class JournalLineFactory extends Factory
{
    protected $model = JournalLine::class;

    public function definition(): array
    {
        $isDebit = $this->faker->boolean();

        return [
            'journal_id' => 1,
            'account_id' => Account::factory(),
            'description' => $this->faker->sentence(),
            'debit' => $isDebit ? $this->faker->randomFloat(2, 100, 10000) : 0,
            'credit' => !$isDebit ? $this->faker->randomFloat(2, 100, 10000) : 0,
            'currency_debit' => 0,
            'currency_credit' => 0,
            'cost_center_id' => null,
            'department_id' => null,
            'branch_id' => null,
            'project_id' => null,
            'tax_id' => null,
            'reference' => null,
        ];
    }

    public function debit(float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'debit' => $amount ?? $this->faker->randomFloat(2, 100, 10000),
            'credit' => 0,
        ]);
    }

    public function credit(float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'debit' => 0,
            'credit' => $amount ?? $this->faker->randomFloat(2, 100, 10000),
        ]);
    }
}
