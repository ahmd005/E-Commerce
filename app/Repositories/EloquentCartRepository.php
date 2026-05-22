<?php
namespace App\Repositories;

use App\Repositories\Contracts\CartDbRepositoryInterface;
use App\Models\Cart; // جدول السلة الرئيسي لربط الزوار أو المستخدمين
use App\Models\CartItem; // جدول عناصر السلة الحية

class EloquentCartRepository implements CartDbRepositoryInterface
{
    public function getItemsByUserId(int $userId): array
{
    $cart = Cart::where('user_id', $userId)->first();

    // إذا لم يكن للمستخدم سلة أصلاً في قاعدة البيانات، نعود بمصفوفة فارغة فوراُ
    if (!$cart) {
        return [];
    }

    // 2. نجلب عناصر هذه السلة المحددة مع تفاصيل المنتجات
    return CartItem::where('cart_id', $cart->id)
        ->with('product') // تأكد أن علاقة product معرفة داخل موديل CartItem
        ->get()
        ->toArray();
}

    public function getItemsByGuestToken(string $guestToken): array
    {
        return CartItem::whereHas('cart', function ($query) use ($guestToken) {
            $query->where('guest_token', $guestToken);
        })->with('product')->get()->toArray();
    }

    public function updateOrCreateItem(array $criteria, array $data): void
    {
        // البحث عن السلة أو إنشاؤها (للمستخدم أو الزائر)
        $cart = Cart::firstOrCreate($criteria);

        // إضافة أو تحديث المنتج داخل هذه السلة
        CartItem::updateOrCreate(
            ['cart_id' => $cart->id, 'product_id' => $data['product_id']],
            ['quantity' => $data['quantity']]
        );
    }

    public function deleteItem(int $userId, int $productId): void
    {
        CartItem::whereHas('cart', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->where('product_id', $productId)->delete();
    }

    public function deleteGuestItem(string $guestToken, int $productId): void
    {
        CartItem::whereHas('cart', function ($query) use ($guestToken) {
            $query->where('guest_token', $guestToken);
        })->where('product_id', $productId)->delete();
    }

    public function clearByUserId(int $userId): void
    {
        Cart::where('user_id', $userId)->delete(); // الحذف المتتالي (Cascade) سيمسح العناصر تلقائياً
    }

    public function clearByGuestToken(string $guestToken): void
    {
        Cart::where('guest_token', $guestToken)->delete();
    }
}