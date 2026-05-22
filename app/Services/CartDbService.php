<?php

namespace App\Services;

use App\Repositories\Contracts\CartDbRepositoryInterface; // مستودع MySQL فقط
use App\Traits\CartKeyTrait;
use Illuminate\Support\Facades\Auth;

class CartDbService
{
    use CartKeyTrait;

    protected $cartDbRepository;

    // حقن مستودع قاعدة البيانات فقط في باني الكلاس
    public function __construct(CartDbRepositoryInterface $cartDbRepository) 
    {
        $this->cartDbRepository = $cartDbRepository;
    }

    /**
     * جلب السلة: قراءة البيانات مباشرة من قاعدة البيانات في كل ريكوست
     */
    public function getCart(): array
    {
        if (!Auth::check()) {
            $guestToken = $this->getCartKey();
                return $this->cartDbRepository->getItemsByGuestToken($guestToken);
           
        }

 return $this->cartDbRepository->getItemsByUserId(Auth::id());
       
    }

    /**
     * إضافة منتج: الكتابة والتعديل مباشرة داخل جداول قاعدة البيانات
     */
    public function addItem(int $productId, int $quantity): void
    {
        $criteria = Auth::check() 
            ? ['user_id' => Auth::id()] 
            : ['guest_token' => $this->getCartKey()];

        $this->cartDbRepository->updateOrCreateItem($criteria, [
            'product_id' => $productId,
            'quantity'   => $quantity
        ]);
    }

    /**
     * حذف منتج: المسح المباشر من قاعدة البيانات
     */
    public function removeItem(int $productId): void
    {
        if (Auth::check()) {
            $this->cartDbRepository->deleteItem(Auth::id(), $productId);
        } elseif ($guestToken = $this->getCartKey()) {
            $this->cartDbRepository->deleteGuestItem($guestToken, $productId);
        }
    }

    /**
     * تفريغ السلة تماماً من قاعدة البيانات
     */
    public function emptyCart(): void
    {
        if (Auth::check()) {
            $this->cartDbRepository->clearByUserId(Auth::id());
        } elseif ($guestToken = $this->getCartKey()) {
            $this->cartDbRepository->clearByGuestToken($guestToken);
        }
    }
}
