<?php


namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CartDbService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CartDBController extends Controller
{
    protected $cartService;

    // حقن خدمة السلة تلقائياً في الباني
    public function __construct(CartDbService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * عرض محتويات السلة
     */
    public function index(): JsonResponse
    {
        $cartItems = $this->cartService->getCart();

        return response()->json([
            'success' => true,
            'data' => $cartItems
        ], 200);
    }

    /**
     * إضافة منتج للسلة أو تحديث كميته
     */
    public function store(Request $request): JsonResponse
    {
        // التحقق من المدخلات
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id', // تأكد من تغيير اسم الجدول لو اختلف
            'quantity'   => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // استدعاء الخدمة لإضافة المنتج
        $this->cartService->addItem(
            $request->input('product_id'),
            $request->input('quantity')
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة المنتج للسلة بنجاح.'
        ], 200);
    }

    /**
     * حذف منتج معين من السلة
     */
    public function destroy(int $productId): JsonResponse
    {
        $this->cartService->removeItem($productId);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المنتج من السلة.'
        ], 200);
    }

    /**
     * تفريغ السلة بالكامل
     */
    public function clear(): JsonResponse
    {
        $this->cartService->emptyCart();

        return response()->json([
            'success' => true,
            'message' => 'تم تفريغ السلة بنجاح.'
        ], 200);
    }
}
