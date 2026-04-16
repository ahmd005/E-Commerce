<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        return DB::transaction(function () use ($request) {

            $user = $request->user();
            $cart = Cart::where('user_id', $user->id)->first();

            $order = Order::create([
                'user_id' => $user->id,
                'total_price' => 0,
                'status' => 'pending'
            ]);

            $total = 0;

            foreach ($cart->items as $item) {

                $product = Product::lockForUpdate()->find($item->product_id);

                if ($product->stock < $item->quantity) {
                    throw new Exception("Out of stock");
                }

                $product->stock -= $item->quantity;
                $product->save();

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item->quantity,
                    'price' => $product->price
                ]);

                $total += $product->price * $item->quantity;
            }

            $order->update(['total_price' => $total]);

            $cart->items()->delete();

            return $order;
        });
    }

    public function index(Request $request) {
        return Order::where('user_id', $request->user()->id)->get();
    }
}
