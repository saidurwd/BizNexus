<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;
use Modules\Core\Models\Tenant;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'code' => $this->faker->unique()->lexify('???'),
            'name' => $this->faker->company(),
            'legal_name' => $this->faker->company().' Ltd.',
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'tax_number' => $this->faker->numerify('TAX-########'),
            'registration_number' => $this->faker->numerify('REG-######'),
            'base_currency_id' => Currency::firstOrCreate(
                ['code' => 'USD'],
                ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'status' => 'active']
            )->id,
            'timezone' => 'Asia/Dhaka',
            'fiscal_year_start' => now()->startOfYear()->format('Y-m-d'),
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
