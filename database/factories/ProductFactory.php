<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\Company;
use Modules\Core\Scopes\CompanyScope;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\Unit;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'sku' => 'SKU-'.$this->faker->unique()->numerify('#####'),
            'name' => ucfirst($this->faker->words(3, true)),
            'type' => Product::TYPE_STOCK,
            'unit_id' => fn (array $attributes) => Unit::withoutGlobalScope(CompanyScope::class)
                ->where('company_id', $attributes['company_id'])
                ->where('code', 'EA')
                ->value('id'),
            'purchase_price' => $this->faker->randomFloat(2, 1, 500),
            'sales_price' => $this->faker->randomFloat(2, 1, 900),
            'costing_method' => Product::COSTING_WEIGHTED_AVERAGE,
            'status' => 'active',
        ];
    }

    public function service(): static
    {
        return $this->state(fn () => ['type' => Product::TYPE_SERVICE]);
    }

    public function nonStock(): static
    {
        return $this->state(fn () => ['type' => Product::TYPE_NON_STOCK]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
