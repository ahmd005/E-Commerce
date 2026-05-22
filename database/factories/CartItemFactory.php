<?php
namespace Database\Factories;

use App\Models\CartItem;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    public function definition(): array
    {
        return [
            // ربط السلة والمنتج تلقائياً عند توليد عناصر السلة
            'cart_id'    => Cart::factory(),
            'product_id' => Product::factory(),
            'quantity'   => $this->faker->numberBetween(1, 5), // كميات عشوائية بين 1 و 5
        ];
    }
}