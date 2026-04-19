<?php

namespace App\Repositories\Contracts;

interface CartRepositoryInterface
{
    public function getUserCart(int $userId);

    public function addToCart(int $userId, int $productId, int $quantity);

    public function removeFromCart(int $itemId);
}
