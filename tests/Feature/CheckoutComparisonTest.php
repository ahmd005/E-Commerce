<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckoutComparisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_safe_checkout_rolls_back_while_unsafe_checkout_leaves_partial_changes(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::create([
            'name' => 'Keyboard',
            'description' => 'Mechanical keyboard',
            'price' => 100,
            'stock' => 5,
        ]);

        $payload = [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
            'fail_after_first_item' => true,
        ];

        $safeResponse = $this->postJson('/api/checkout', $payload);

        $safeResponse->assertStatus(500);
        $safeResponse->assertJson([
            'message' => 'Checkout failed',
            'mode' => 'safe_transactional_checkout',
        ]);

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $unsafeResponse = $this->postJson('/api/checkout/unsafe', $payload);

        $unsafeResponse->assertStatus(500);
        $unsafeResponse->assertJson([
            'message' => 'Checkout failed',
            'mode' => 'unsafe_non_transactional_checkout',
        ]);

        $this->assertSame(4, $product->fresh()->stock);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
    }
}
