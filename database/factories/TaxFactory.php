<?php

namespace Database\Factories;

use Modules\Finance\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxFactory extends Factory
{
    protected $model = Tax::class;

    public function definition(): array
    {
        $types = ['VAT', 'WITHHOLDING_TAX', 'INCOME_TAX', 'OTHER'];

        return [
            'company_id' => \Modules\Core\Models\Company::factory(),
            'tax_code' => 'TAX-' . $this->faker->unique()->numerify('###'),
            'tax_name' => $this->faker->words(2, true) . ' Tax',
            'tax_type' => $this->faker->randomElement($types),
            'rate' => $this->faker->randomFloat(2, 0, 30),
            'is_inclusive' => false,
            'input_account_id' => null,
            'output_account_id' => null,
            'status' => 'active',
        ];
    }

    public function vat(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_type' => 'VAT',
            'tax_name' => 'Value Added Tax',
            'rate' => 15.00,
        ]);
    }

    public function inclusive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_inclusive' => true,
        ]);
    }

    public function exclusive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_inclusive' => false,
        ]);
    }
}
