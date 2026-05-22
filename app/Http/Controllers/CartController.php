<?php
namespace App\Http\Controllers;

use App\Http\Requests\AddToCartRequest;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    protected $cartService;

    // حقن الخدمة داخل الكنترولر تلقائياً
    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => $this->cartService->getCart()
        ],200);
    }

    public function store(AddToCartRequest $request): JsonResponse
    {
        $this->cartService->addItem(
            $request->validated('product_id'),
            $request->validated('quantity')
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Product added to cart successfully.'
        ]);
    }

    public function destroy(int $productId): JsonResponse
    {
        $this->cartService->removeItem($productId);

        return response()->json([
            'status'  => 'success',
            'message' => 'Product removed from cart.'
        ]);
    }

    public function clear(): JsonResponse
    {
        $this->cartService->emptyCart();

        return response()->json([
            'status'  => 'success',
            'message' => 'Cart cleared successfully.'
        ]);
    }
}