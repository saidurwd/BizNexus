<?php

namespace Database\Factories;

use Modules\Core\Models\BusinessUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessUnitFactory extends Factory
{
    protected $model = BusinessUnit::class;

    public function definition(): array
    {
        return [
            'company_id' => \Modules\Core\Models\Company::factory(),
            'code' => $this->faker->unique()->lexify('BU???'),
            'name' => $this->faker->company() . ' Business Unit',
            'status' => 'active',
        ];
    }
}
