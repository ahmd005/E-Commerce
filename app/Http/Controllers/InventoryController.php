<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\InventoryService;
use App\Models\Product;

class InventoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Business Logic فقط - بلا Timing, بلا Request ID توليد
     * الـ Middleware يتولى: Tracing, Performance Monitoring, Error Handling, Logging
     */
    public function testStock(Request $request)
    {
        // ============ VALIDATION (Business Logic) ============
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'mode' => ['nullable', 'string', 'in:before,after'],
        ]);

        $productId = (int) $validated['product_id'];
        $quantity = (int) $validated['quantity'];
        $mode = strtolower((string) ($validated['mode'] ?? $request->query('mode', 'after')));

        // ============ BUSINESS LOGIC ============
        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'error' => "Product {$productId} not found",
            ], 404);
        }

        $stockBefore = $product->stock;

        // تنفيذ التحديث
        $newStock = $this->inventoryService->updateStock($productId, $quantity, $mode);

        $productAfter = Product::find($productId);
        $stockAfter = $productAfter->stock;

        // ============ RESPONSE (بدون Timing - الـ Middleware يضيفها) ============
        return response()->json([
            'success' => true,
            'mode' => $mode,
            'product_id' => $productId,
            'quantity_attempted' => $quantity,
            'stock' => [
                'before' => $stockBefore,
                'after' => $stockAfter,
                'deducted' => $stockBefore - $stockAfter,
            ],
            'message' => "Stock updated successfully",
        ], 200);
    }
}