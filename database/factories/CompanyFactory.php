<?php

namespace Database\Factories;

use Modules\Core\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->lexify('???'),
            'name' => $this->faker->company(),
            'legal_name' => $this->faker->company() . ' Ltd.',
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'tax_number' => $this->faker->numerify('TAX-########'),
            'registration_number' => $this->faker->numerify('REG-######'),
            'base_currency_id' => 1,
            'timezone' => 'Asia/Dhaka',
            'fiscal_year_start' => now()->startOfYear()->format('Y-m-d'),
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
