<?php
namespace App\Repositories\Contracts;

interface CartRepositoryInterface
{
    public function getItems(string $key): array;
    public function updateItem(string $key, int $productId, int $quantity): void;
    public function removeItem(string $key, int $productId): void;
    public function clear(string $key): void;
    public function setExpiry(string $key, int $ttlInSeconds): void;
}