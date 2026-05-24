<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
public function run(): void
{
    // نقوم بتوليد 20,000 طلب على 20 دفعة، كل دفعة 1000 طلب فقط
    foreach (range(1, 20) as $i) {
        \App\Models\Order::factory()->count(1000)->create([
            'status' => 'completed'
        ]);
        
        // طباعة مؤشر مرئي في التيرمنال لكي تعرف أن السيرفر يعمل ولا يزال حياً
        $this->command->info("Batch {$i}/20 created successfully.");
    }
}
}
