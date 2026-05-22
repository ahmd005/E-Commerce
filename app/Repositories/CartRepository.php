<?php
namespace App\Repositories;

use App\Repositories\Contracts\CartRepositoryInterface;
use Illuminate\Support\Facades\Redis;

class CartRepository implements CartRepositoryInterface
{
    public function getItems(string $key): array
    {
        // جلب جميع العناصر المخزنة في الـ Hash
        $items = Redis::hgetall($key);

        return array_map(function ($item) {
            return json_decode($item, true);
        }, $items);
    }

    public function updateItem(string $key, int $productId, int $quantity): void
    {
        // تخزين تفاصيل المنتج كـ JSON داخل حقل الـ Product ID
        Redis::hset($key, $productId, json_encode([
            'product_id' => $productId,
            'quantity'   => $quantity,
            'updated_at' => now()->toDateTimeString()
        ]));
    }

    public function removeItem(string $key, int $productId): void
    {
        Redis::hdel($key, $productId);
    }

    public function clear(string $key): void
    {
        Redis::del($key);
    }

    public function setExpiry(string $key, int $ttlInSeconds): void
    {
        Redis::expire($key, $ttlInSeconds);
    }
}