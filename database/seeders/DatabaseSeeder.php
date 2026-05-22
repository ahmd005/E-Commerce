<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. أولاً: ننشئ مجموعة من المنتجات الثابتة في النظام (مثلاً 30 منتج)
        // هذا يضمن وجود منتجات مسبقاً في الداتا بيز تختار منها السلال تلقائياً
        $products = Product::factory(30)->create();

        // 2. ثانياً: إنشاء 10 مستخدمين عشوائيين مع سلالهم
        User::factory(10)->create()->each(function ($user) use ($products) {
            $cart = Cart::factory()->create(['user_id' => $user->id]);
            
            // إضافة من 10 إلى 15 منتج عشوائي مستخدمين المنتجات التي أنشأناها مسبقاً
            CartItem::factory(rand(10, 15))->create([
                'cart_id'    => $cart->id,
                'product_id' => $products->random()->id, // اختيار ذكي وسريع من المصفوفة بدلاً من استعلام DB في كل مرة
            ]);
        });

        // 3. ثالثاً: التعامل مع المستخدم الخاص (11004) "خارج الحلقة تماماً"
        // نستخدم firstOrCreate لضمان وجود المستخدم أولاً حتى لا ينهار السيرفر
        $specialUser = User::firstOrCreate(
            ['id' => 11004],
            [
                'name'     => 'Special User',
                'email'    => 'user11004@example.com',
                'password' => bcrypt('password'), // أو Hash::make('password')
            ]
        );

        // إنشاء سلة واحدة فقط للمستخدم الخاص
        $specialCart = Cart::factory()->create(['user_id' => $specialUser->id]);

        // إضافة 10 منتجات عشوائية له من المنتجات المتوفرة
        CartItem::factory()->count(10)->create([
            'cart_id'    => $specialCart->id,
            'product_id' => $products->random()->id,
        ]);
    }
}