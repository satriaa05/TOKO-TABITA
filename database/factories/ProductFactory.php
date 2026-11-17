<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 1000, 50000);

        return [
            'barcode' => fake()->unique()->numerify('899###########'),
            'name' => fake()->words(3, true),
            'category_id' => Category::factory(),
            'unit' => fake()->randomElement(['pcs', 'bungkus', 'botol', 'karton']),
            'cost_price' => $cost,
            'sale_price' => round($cost * fake()->randomFloat(2, 1.1, 1.5), 0),
            'stock' => fake()->numberBetween(0, 100),
            'min_stock' => 10,
            'is_active' => true,
        ];
    }

    /**
     * Produk dengan stok menipis.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 2,
            'min_stock' => 10,
        ]);
    }

    /**
     * Produk nonaktif.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
