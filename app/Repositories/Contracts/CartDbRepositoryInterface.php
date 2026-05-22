<?php

namespace App\Repositories\Contracts;

interface CartDbRepositoryInterface
{
    public function getItemsByUserId(int $userId): array;
    public function getItemsByGuestToken(string $guestToken): array;
    public function updateOrCreateItem(array $criteria, array $data): void;
    public function deleteItem(int $userId, int $productId): void;
    public function deleteGuestItem(string $guestToken, int $productId): void;
    public function clearByUserId(int $userId): void;
    public function clearByGuestToken(string $guestToken): void;
}
