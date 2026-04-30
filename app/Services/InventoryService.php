<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * InventoryService - خدمة إدارة المخزون
 * 
 * Business Logic فقط:
 * - BEFORE: مسار غير محمي (يظهر Race Condition)
 * - AFTER: مسار محمي (Transaction + Atomic Update)
 * 
 * الـ AOP Middleware يتولى: Tracing, Performance Monitoring, Error Handling, Logging
 */
class InventoryService
{
    /**
     * تحديث المخزون بطريقتين مختلفتين
     * Business Logic فقط - بلا Timing, بلا Logging
     */
    public function updateStock($productId, $quantity, $mode)
    {
        if ($mode === 'before') {
            // ❌ مسار ضعيف: لا يستخدم حماية
            $product = Product::find($productId);
            if (!$product) throw new Exception("Product not found");

            $stockBefore = $product->stock;
            usleep(100000); // تأخير متعمد لإظهار المشكلة

            DB::table('products')
                ->where('id', $productId)
                ->decrement('stock', $quantity);

            return Product::find($productId)->stock;
        } 
        else if ($mode === 'after') {
            // ✅ مسار محمي: استخدام Transaction + Atomic Update
            return DB::transaction(function () use ($productId, $quantity) {
                $product = Product::find($productId);
                if (!$product) {
                    throw new Exception("Product not found");
                }
           Log::info("Attempting protected stock deduction for product: $productId");
                $affected = DB::table('products')
                    ->where('id', $productId)
                    ->where('stock', '>=', $quantity)
                    
                    ->decrement('stock', $quantity);

                if ($affected === 0) {
                    throw new Exception("Out of stock");
                }

                return Product::find($productId)->stock;
            }, 3);
        }
        else {
            throw new Exception("Invalid mode");
        }
    }
}