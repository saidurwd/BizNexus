<?php

namespace Database\Factories;

use Modules\Core\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->currencyCode(),
            'name' => $this->faker->word() . ' Currency',
            'symbol' => $this->faker->randomElement(['$', '€', '£', '৳', '¥']),
            'decimal_places' => $this->faker->randomElement([0, 2, 3, 4]),
            'status' => 'active',
        ];
    }
}
