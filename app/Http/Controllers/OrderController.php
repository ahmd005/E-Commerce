<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;
use App\Jobs\ProcessOrder; 


class OrderController extends Controller
{

public function before(Request $request) {
    $product = Product::find(1); 
    $currentStock = $product->stock;

     usleep(500000); 

    if ($currentStock > 0) {
        $product->stock = $currentStock - 1;
        $product->save();

        return response()->json([
            'status' => 'VULNERABLE_SUCCESS',
            'stock_after' => $product->stock  
        ]);
    }

    return response()->json(['status' => 'OUT_OF_STOCK'], 400);
}


public function after(Request $request) {
    $productId = $request->product_id;

   
    $pool = Cache::lock('my_thread_pool', 10); 

    try {
        return $pool->block(5, function () use ($productId) {
            
            return DB::transaction(function () use ($productId) {
                $product = Product::lockForUpdate()->find($productId);
                
                if ($product && $product->stock > 0) {
                    $product->decrement('stock');
                    
                    return response()->json([
                        'status' => 'SAFE_SUCCESS',
                        'stock_after' => $product->stock,
                        'info' => 'Processed via Thread Pool'
                    ]);
                }
                
                return response()->json(['status' => 'OUT_OF_STOCK'], 400);
            });
        });
    } catch (Exception $e) {
        return response()->json([
            'status' => 'SERVER_BUSY',
            'message' => 'Thread Pool is full, try again later'
        ], 503);
    }
}


    public function resetStock(Request $request)
    {
        $productId = $request->product_id ?? 1;
        $product = Product::find($productId);
        
        if ($product) {
            $product->update(['stock' => 10]);
            return response()->json(['message' => 'Stock reset to 10 successfully']);
        }
        
        return response()->json(['message' => 'Product not found'], 404);
    }

}
