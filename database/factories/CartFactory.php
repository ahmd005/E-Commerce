<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition(): array
    {
        return [
            // إنشاء مستخدم جديد تلقائياً وربطه بالسلة في حال لم نقم بتمريره يدوياً
            'user_id' => User::factory(), 
        ];
    }
}