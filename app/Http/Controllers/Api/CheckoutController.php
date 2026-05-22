<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function store(Request $request)
    {
        return $this->checkout($request, true, true);
    }

    public function storeUnsafe(Request $request)
    {
        return $this->checkout($request, false, false);
    }

    private function checkout(Request $request, bool $useTransaction, bool $usePessimisticLock)
    {
        $user = $request->user() ?? \App\Models\User::first();

        if (!$user) {
            return response()->json(['message' => 'No users found in database. Please seed users table.'], 404);
        }

        $items = $request->input('items', []);

        if (!is_array($items) || empty($items)) {
            return response()->json(['message' => 'items is required'], 422);
        }

        $failAfterFirstItem = $request->boolean('fail_after_first_item');
        $simulateRace = $request->boolean('simulate_race', false);
        $delayMs = max(0, (int) $request->input('delay_ms', 50));

        $work = function () use (
            $items,
            $user,
            $failAfterFirstItem,
            $usePessimisticLock,
            $useTransaction,
            $simulateRace,
            $delayMs
        ) {
            $totalPrice = 0;
            $lastObservedStock = null;
            $lastObservedBalance = null;

            $order = null;

            foreach ($items as $index => $item) {
                $query = Product::query()->whereKey($item['product_id']);

                if ($usePessimisticLock) {
                    $query->lockForUpdate();
                }

                $product = $query->first();

                if (!$product) {
                    throw new \RuntimeException('Product not found');
                }

                $userQuery = \App\Models\User::query()->whereKey($user->id);

                if ($usePessimisticLock) {
                    $userQuery->lockForUpdate();
                }

                $userRow = $userQuery->first();

                if (!$userRow) {
                    throw new \RuntimeException('User not found');
                }

                $observedStock = $product->stock;
                $observedBalance = $userRow->balance;
                $lastObservedStock = $observedStock;
                $lastObservedBalance = $observedBalance;

                if (!$useTransaction && $simulateRace && $delayMs > 0) {
                    usleep($delayMs * 1000);
                }

                $lineCost = (float) $product->price * (int) $item['quantity'];

                if (!$useTransaction && $product->stock <= 0) {
                    return response()->json([
                        'message' => 'Out of stock',
                        'mode' => 'unsafe_non_transactional_checkout',
                        'observed_stock' => $lastObservedStock,
                        'observed_balance' => $lastObservedBalance,
                    ], 409);
                }

                if ($useTransaction && $product->stock < $item['quantity']) {
                    return response()->json([
                        'message' => 'Insufficient stock for product ' . $product->id,
                        'mode' => 'safe_transactional_checkout',
                        'observed_stock' => $lastObservedStock,
                        'observed_balance' => $lastObservedBalance,
                    ], 409);
                }

                if ($useTransaction && $userRow->balance < $lineCost) {
                    return response()->json([
                        'message' => 'Insufficient balance for user ' . $userRow->id,
                        'mode' => 'safe_transactional_checkout',
                        'observed_stock' => $lastObservedStock,
                        'observed_balance' => $lastObservedBalance,
                    ], 409);
                }

                if (!$order) {
                    $order = Order::create([
                        'user_id' => $user->id,
                        'status' => 'processing',
                        'total_price' => 0,
                    ]);
                }

                $product->decrement('stock', $item['quantity']);

                if ($failAfterFirstItem && $index === 0) {
                    throw new \RuntimeException('Simulated failure after the first item');
                }

                $userRow->decrement('balance', $lineCost);

                if (!$useTransaction && $simulateRace) {
                    $lastObservedStock = $observedStock;
                }

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);

                $totalPrice += $lineCost;
            }

            if (!$order) {
                throw new \RuntimeException('Order could not be created');
            }

            $order->update(['total_price' => $totalPrice, 'status' => 'completed']);

            $responseData = [
                'message' => 'Checkout completed successfully',
                'order_id' => $order->id,
                'total_spent' => $totalPrice,
                'mode' => $useTransaction ? 'safe_transactional_checkout' : 'unsafe_non_transactional_checkout',
                'observed_stock' => $lastObservedStock,
                'observed_balance' => $lastObservedBalance,
            ];

            if (!$useTransaction && $simulateRace) {
                $responseData['delay_ms'] = $delayMs;
            }

            return response()->json($responseData);
        };

        try {
            if ($useTransaction) {
                return DB::transaction($work);
            }

            return $work();
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Checkout failed',
                'error' => $e->getMessage(),
                'mode' => $useTransaction ? 'safe_transactional_checkout' : 'unsafe_non_transactional_checkout',
            ], 500);
        }
    }
}




// & "C:\Program Files\k6\k6.exe" run -e MODE=safe -e USERS=101 -e PRODUCT_ID=2 -e STOCK=100 -e QUANTITY=1 -e DELAY_MS=500 -e HOST=http://127.0.0.1 -e PORTS=8000,8001,8002 compare-test.js
