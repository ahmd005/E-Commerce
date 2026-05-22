<?php
namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait CartKeyTrait
{
    /**
     * توليد مفتاح فريد للسلة في Redis
     */
    protected function getCartKey(): string
    {
        if (request()->bearerToken() && $user = auth('sanctum')->user()) {
        return "cart:user:" . $user->id;
    }

    // إذا لم يكن مسجلاً، نعتمد على توكن الزائر
    $guestToken = request()->header('X-Cart-Token', request()->cookie('cart_token'));
    return "cart:guest:" . $guestToken;
    }
}