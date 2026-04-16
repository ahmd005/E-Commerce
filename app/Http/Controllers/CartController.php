<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function getCart(Request $request) {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        return $cart->load('items.product');
    }

    public function add(Request $request) {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);

        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($item) {
            $item->quantity += $request->quantity;
            $item->save();
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity
            ]);
        }

        return response()->json(['message' => 'Added']);
    }

    public function remove(Request $request) {
        CartItem::where('id', $request->item_id)->delete();
        return response()->json(['message' => 'Removed']);
    }
}