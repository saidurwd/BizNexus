<?php

namespace Database\Factories;

use Modules\Finance\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'company_id' => \Modules\Core\Models\Company::factory(),
            'supplier_code' => 'SUP-' . $this->faker->unique()->numerify('####'),
            'name' => $this->faker->company(),
            'contact_person' => $this->faker->name(),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'tax_number' => $this->faker->numerify('TAX-########'),
            'currency_id' => null,
            'payable_account_id' => null,
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
