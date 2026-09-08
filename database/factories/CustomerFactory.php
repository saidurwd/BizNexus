<?php

namespace Database\Factories;

use Modules\Finance\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'company_id' => \Modules\Core\Models\Company::first()?->id ?? 1,
            'customer_code' => 'CUS-' . $this->faker->unique()->numerify('####'),
            'name' => $this->faker->company(),
            'contact_person' => $this->faker->name(),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'tax_number' => $this->faker->numerify('TAX-########'),
            'currency_id' => 1,
            'receivable_account_id' => null,
            'status' => 'active',
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
