<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\CartRepository;
class CartController extends Controller
{


    public function __construct(private CartRepositoryInterface $cartRepository)
    {
        
    }
    public function getCart() {
        $userId = auth()->id();
        $cart = $this->cartRepository->getUserCart($userId); 
        return response()->json($cart,200);

    }

    public function add(Request $request) {
        $userId = auth()->id();
        $this->cartRepository->addToCart($userId, $request->product_id, $request->quantity);

        return response()->json(['message' => 'Added'],200);
    }
    public function update(Request $request) {
        $userId = auth()->id();
        $this->cartRepository->addToCart($userId, $request->product_id, $request->quantity);
        return response()->json(['message' => 'Updated'],200);
    }

    public function remove(Request $request) {
        try{
            $this->cartRepository->removeFromCart($request->item_id);
            return response()->json(['message' => 'Removed'],200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
        
    }
}