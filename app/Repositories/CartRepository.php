<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\CartItem;

class CartRepository implements Contracts\CartRepositoryInterface
{
    
   public function getUserCart(int $userId)
   {    
       $cart = Cart::where('user_id', $userId)->first();
       return $cart;
   }

   public function addToCart(int $userId, int $productId, int $quantity)
   {        
       $cart = $this->getUserCart($userId); 
       
       if (!$cart) {
           $cart = Cart::create(['user_id' => $userId]);
       }
       
       $item = CartItem::where('cart_id', $cart->id)
               ->where('product_id', $productId)
               ->first();
       
       if ($item) {
           $item->quantity += $quantity;
           $item->save();
       } else {
           CartItem::create([
               'cart_id' => $cart->id,
               'product_id' => $productId,
               'quantity' => $quantity
           ]);
       }
   }

   public function removeFromCart(int $itemId)
   {
      $item = CartItem::find($itemId);
      if ($item) {
          $item->delete();
      } else {
          throw new \Exception('Item not found in cart');
      }   
   }

}
