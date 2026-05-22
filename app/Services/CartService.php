<?php
namespace App\Services;

use App\Repositories\Contracts\CartRepositoryInterface;
use App\Traits\CartKeyTrait;
use App\Repositories\Contracts\CartDbRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class CartService
{
    use CartKeyTrait;

    protected $cartRedisRepository;
    protected $cartDbRepository;

    // تم جلب القيمة ديناميكياً من إعدادات الجلسة في لارافيل، وتحويلها إلى ثوانٍ
    // إذا انتهت الجلسة، تنتهي صلاحية مفتاح Redis تلقائياً
    protected function getTtl(): int
    {
        return config('session.lifetime') * 60; // يحول الدقائق في الإعدادات إلى ثوانٍ
    }

    public function __construct(
        CartRepositoryInterface $cartRedisRepository,
        CartDbRepositoryInterface $cartDbRepository
    ) {
        $this->cartRedisRepository = $cartRedisRepository;
        $this->cartDbRepository = $cartDbRepository;
    }
public function getCart(): array
{
    $key = $this->getCartKey();
    
    // 1. حاول جلب العناصر من Redis
    $redisItems = $this->cartRedisRepository->getItems($key);

    // 2. إذا كان المفتاح غير موجود (Redis فارغ)
    if (empty($redisItems)) {
        
        // جلب العناصر المخزنة مسبقاً في قاعدة البيانات (Eloquent)
        $dbItems = $this->cartDbRepository->getItemsByUserId((int) Auth::id());

        // 3. إذا وجدنا عناصر في قاعدة البيانات، نقوم بتعبئة الكاش (Redis) لأول مرة
        if (!empty($dbItems)) {
            foreach ($dbItems as $item) {
                // نمرر المفتاح، رقم المنتج، والكمية لتدخل داخل الـ Hash
                $this->cartRedisRepository->updateItem($key, $item['product_id'], $item['quantity']);
            }
            
            // تعيين وقت انتهاء صلاحية المفتاح (نهاية الجلسة مثلاً)
            $this->cartRedisRepository->setExpiry($key, $this->getTtl());
            
            // إعادة جلب البيانات من Redis بعد تعبئتها لتظهر بالشكل المطلوب
            $redisItems = $this->cartRedisRepository->getItems($key);
        }
    }

    return $redisItems;
}
    public function addItem(int $productId, int $quantity): void
    {
        $key = $this->getCartKey();
        
        // 1. التحديث في Redis
        $this->cartRedisRepository->updateItem($key, $productId, $quantity);
        $this->cartRedisRepository->setExpiry($key, $this->getTtl());

        // 2. التحديث في قاعدة البيانات لضمان عدم ضياع البيانات بعد انتهاء الـ TTL
        $this->cartDbRepository->updateOrCreateItem(
            ['user_id' => Auth::id()],
            ['product_id' => $productId, 'quantity' => $quantity]
        );
    }

    public function removeItem(int $productId): void
    {
        $key = $this->getCartKey();
        
        $this->cartRedisRepository->removeItem($key, $productId);
        $this->cartDbRepository->deleteItem((int) Auth::id(), $productId);
    }

    public function emptyCart(): void
    {
        $key = $this->getCartKey();
        
        $this->cartRedisRepository->clear($key);
        $this->cartDbRepository->clearByUserId((int) Auth::id());
    }
}