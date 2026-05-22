<?php
namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name'        => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'price'       => $this->faker->randomFloat(2, 10, 500), // أسعار بين 10 و 500
            'stock'       => $this->faker->numberBetween(5, 100),
        ];
    }
}