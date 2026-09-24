<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\Tenant;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('TEN-####'),
            'name' => $this->faker->company(),
            'data_region' => null,
            'status' => 'active',
        ];
    }
}
