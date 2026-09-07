<?php

namespace Database\Factories;

use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;
use Illuminate\Database\Eloquent\Factories\Factory;

class JournalFactory extends Factory
{
    protected $model = Journal::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'journal_number' => 'JV-' . date('Y') . '-' . str_pad($this->faker->unique()->numberBetween(1, 9999), 6, '0', STR_PAD_LEFT),
            'journal_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'posting_date' => null,
            'fiscal_period_id' => null,
            'reference_type' => null,
            'reference_id' => null,
            'description' => $this->faker->sentence(),
            'status' => Journal::STATUS_DRAFT,
            'currency_id' => 1,
            'exchange_rate' => 1.00000000,
            'total_debit' => 0,
            'total_credit' => 0,
            'posted_at' => null,
            'posted_by' => null,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Journal::STATUS_DRAFT,
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Journal::STATUS_SUBMITTED,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Journal::STATUS_APPROVED,
        ]);
    }

    public function posted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Journal::STATUS_POSTED,
            'posting_date' => now(),
            'posted_at' => now(),
            'posted_by' => 1,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Journal::STATUS_CANCELLED,
        ]);
    }

    public function reversed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Journal::STATUS_REVERSED,
        ]);
    }
}
