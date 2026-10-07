<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

use Tests\TestCase;

class AcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.wixwood_admin.token' => 'test-admin-token-12345',
        ]);
    }

    /** 1. Submit a valid review -> stored pending -> not in GET /api/reviews */
    public function test_01_submit_valid_review_stores_pending_and_not_in_public_index(): void
    {
        $response = $this->postJson('/api/reviews', [
            'rating' => 5,
            'name' => 'Adewale',
            'piece' => 'Dining Table',
            'comment' => 'Beautiful craftsmanship and top tier wood finish!',
        ]);

        $response->assertStatus(201)
            ->assertJson(['ok' => true]);

        $reviewId = $response->json('id');
        $this->assertNotNull($reviewId);

        $this->assertDatabaseHas('reviews', [
            'id' => $reviewId,
            'status' => 'pending',
            'name' => 'Adewale',
        ]);

        $publicRes = $this->getJson('/api/reviews');
        $publicRes->assertStatus(200)
            ->assertJson(['reviews' => []]);
    }

    /** 2. Admin lists pending (with token) -> approve -> now in GET /api/reviews */
    public function test_02_admin_approve_review_makes_it_public(): void
    {
        $review = Review::create([
            'rating' => 5,
            'name' => 'Bisi',
            'piece' => 'Hardwood Stool',
            'comment' => 'Sturdy and beautiful stool.',
            'status' => 'pending',
        ]);

        $adminRes = $this->withHeaders(['x-admin-token' => 'test-admin-token-12345'])
            ->getJson('/api/admin/reviews?status=pending');

        $adminRes->assertStatus(200);
        $this->assertCount(1, $adminRes->json('reviews'));

        $approveRes = $this->withHeaders(['x-admin-token' => 'test-admin-token-12345'])
            ->postJson("/api/admin/reviews/{$review->id}/approve");

        $approveRes->assertStatus(200)->assertJson(['ok' => true]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'status' => 'approved',
        ]);

        $publicRes = $this->getJson('/api/reviews');
        $publicRes->assertStatus(200);
        $this->assertCount(1, $publicRes->json('reviews'));
        $this->assertEquals($review->id, $publicRes->json('reviews.0.id'));
    }

    /** 3. Invalid rating (9) -> 400/422 */
    public function test_03_invalid_rating_rejected(): void
    {
        $response = $this->postJson('/api/reviews', [
            'rating' => 9,
            'comment' => 'Invalid rating review comment.',
        ]);

        $response->assertStatus(422);
    }

    /** 4. Honeypot _hp filled -> 200 but nothing stored */
    public function test_04_honeypot_filled_returns_200_without_storing(): void
    {
        $response = $this->postJson('/api/reviews', [
            '_hp' => 'bot_value',
            'rating' => 5,
            'comment' => 'Spam review content here.',
        ]);

        $response->assertStatus(200)->assertJson(['ok' => true]);
        $this->assertDatabaseCount('reviews', 0);
    }

    /** 5. Any /api/admin/* without token -> 401 */
    public function test_05_admin_routes_without_token_return_401(): void
    {
        $this->getJson('/api/admin/reviews')->assertStatus(401);
        $this->getJson('/api/admin/orders')->assertStatus(401);
    }

    /** 6. Price integrity: client-sent price is ignored, totals computed server-side */
    public function test_06_price_integrity_ignores_client_price(): void
    {
        config([
            'catalog.products.hardwood-stool.price' => 45000,
            'catalog.delivery_zones.lagos-mainland.fee' => 5000,
        ]);

        $response = $this->postJson('/api/orders', [
            'items' => [
                ['id' => 'hardwood-stool', 'qty' => 2, 'price' => 1], // forged price 1
            ],
            'customer' => [
                'name' => 'Chidi',
                'phone' => '+2348012345678',
                'email' => 'chidi@example.com',
            ],
            'delivery' => [
                'address' => '12 Allen Avenue',
                'zone' => 'lagos-mainland',
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'ok' => true,
                'subtotal' => 90000,
                'deliveryFee' => 5000,
                'total' => 95000,
                'currency' => 'NGN',
            ]);
    }

    /** 7. Product id not in catalog -> 400 */
    public function test_07_invalid_product_id_rejected(): void
    {
        config(['catalog.delivery_zones.lagos-mainland.fee' => 5000]);

        $response = $this->postJson('/api/orders', [
            'items' => [
                ['id' => 'nonexistent-table', 'qty' => 1],
            ],
            'customer' => ['name' => 'Test User', 'phone' => '08000000000'],
            'delivery' => ['address' => 'Test Address', 'zone' => 'lagos-mainland'],
        ]);

        $response->assertStatus(400);
    }

    /** 8. Delivery zone whose fee is null -> 400 */
    public function test_08_null_delivery_zone_fee_rejected(): void
    {
        config([
            'catalog.products.hardwood-stool.price' => 45000,
            'catalog.delivery_zones.abuja.fee' => null,
        ]);

        $response = $this->postJson('/api/orders', [
            'items' => [
                ['id' => 'hardwood-stool', 'qty' => 1],
            ],
            'customer' => ['name' => 'Test User', 'phone' => '08000000000'],
            'delivery' => ['address' => 'Abuja Street', 'zone' => 'abuja'],
        ]);

        $response->assertStatus(400);
    }

    /** 9. Product whose price is null -> 400 */
    public function test_09_null_product_price_rejected(): void
    {
        config([
            'catalog.products.handled-cup.price' => null,
            'catalog.delivery_zones.lagos-mainland.fee' => 5000,
        ]);

        $response = $this->postJson('/api/orders', [
            'items' => [
                ['id' => 'handled-cup', 'qty' => 1],
            ],
            'customer' => ['name' => 'Test User', 'phone' => '08000000000'],
            'delivery' => ['address' => 'Lagos Street', 'zone' => 'lagos-mainland'],
        ]);

        $response->assertStatus(400);
    }

    /** 10. Pickup zone (fee 0) + 1 stool -> total 45000 */
    public function test_10_pickup_zone_zero_fee_order(): void
    {
        config([
            'catalog.products.hardwood-stool.price' => 45000,
            'catalog.delivery_zones.pickup.fee' => 0,
        ]);

        $response = $this->postJson('/api/orders', [
            'items' => [
                ['id' => 'hardwood-stool', 'qty' => 1],
            ],
            'customer' => ['name' => 'Pickup Customer', 'phone' => '08000000000'],
            'delivery' => ['address' => 'Workshop Pickup', 'zone' => 'pickup'],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'subtotal' => 45000,
                'deliveryFee' => 0,
                'total' => 45000,
            ]);
    }

    /** 11. Admin can list/filter orders, change status; invalid status -> 4xx */
    public function test_11_admin_order_management_and_status_validation(): void
    {
        config([
            'catalog.products.hardwood-stool.price' => 45000,
            'catalog.delivery_zones.pickup.fee' => 0,
        ]);

        $order = Order::create([
            'id' => 'WW-TEST12345',
            'customer_name' => 'Admin Test Customer',
            'phone' => '08000000000',
            'address' => 'Workshop',
            'items' => [],
            'currency' => 'NGN',
            'subtotal' => 45000,
            'delivery_fee' => 0,
            'total' => 45000,
            'delivery_zone' => 'pickup',
            'payment_status' => 'unpaid',
            'order_status' => 'pending_payment',
        ]);

        $adminTokenHeader = ['x-admin-token' => 'test-admin-token-12345'];

        $listRes = $this->withHeaders($adminTokenHeader)->getJson('/api/admin/orders');
        $listRes->assertStatus(200);
        $this->assertCount(1, $listRes->json('orders'));

        $updateRes = $this->withHeaders($adminTokenHeader)
            ->postJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'in_production',
            ]);
        $updateRes->assertStatus(200)->assertJson(['ok' => true]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'in_production',
        ]);

        $invalidRes = $this->withHeaders($adminTokenHeader)
            ->postJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'nonexistent_status',
            ]);
        $invalidRes->assertStatus(422);
    }

    /** 12. Mark-paid-manually flips payment_status and order_status to paid */
    public function test_12_mark_paid_manually(): void
    {
        $order = Order::create([
            'id' => 'WW-MANUAL123',
            'customer_name' => 'Manual Customer',
            'phone' => '08000000000',
            'address' => 'Lagos',
            'items' => [],
            'currency' => 'NGN',
            'subtotal' => 10000,
            'delivery_fee' => 1000,
            'total' => 11000,
            'delivery_zone' => 'lagos-mainland',
            'payment_status' => 'unpaid',
            'order_status' => 'pending_payment',
        ]);

        $response = $this->withHeaders(['x-admin-token' => 'test-admin-token-12345'])
            ->postJson("/api/admin/orders/{$order->id}/mark-paid-manually");

        $response->assertStatus(200)->assertJson(['ok' => true]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'order_status' => 'paid',
            'payment_provider' => 'manual',
        ]);
    }

    /** 13. Webhook with a bad signature changes nothing */
    public function test_13_webhook_bad_signature_ignored(): void
    {
        config(['services.paystack.secret_key' => 'sk_test_secret_key_123']);

        $order = Order::create([
            'id' => 'WW-BADSIG123',
            'customer_name' => 'Bad Sig Customer',
            'phone' => '08000000000',
            'address' => 'Lagos',
            'items' => [],
            'currency' => 'NGN',
            'subtotal' => 10000,
            'delivery_fee' => 1000,
            'total' => 11000,
            'delivery_zone' => 'lagos-mainland',
            'payment_status' => 'unpaid',
            'order_status' => 'pending_payment',
            'payment_reference' => 'ref_badsig_123',
        ]);

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'ref_badsig_123',
                'amount' => 1100000,
            ],
        ];

        $response = $this->withHeaders(['x-paystack-signature' => 'invalid_signature_string'])
            ->postJson('/api/webhooks/paystack', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'unpaid',
        ]);
    }

    /** Paystack initialize & verified webhook flow test */
    public function test_14_paystack_init_and_verified_webhook(): void
    {
        $secretKey = 'sk_test_secret_key_999';
        config(['services.paystack.secret_key' => $secretKey]);

        $order = Order::create([
            'id' => 'WW-PAYSTACK001',
            'customer_name' => 'Paystack Customer',
            'phone' => '08000000000',
            'email' => 'customer@example.com',
            'address' => 'Lagos',
            'items' => [],
            'currency' => 'NGN',
            'subtotal' => 45000,
            'delivery_fee' => 5000,
            'total' => 50000,
            'delivery_zone' => 'lagos-mainland',
            'payment_status' => 'unpaid',
            'order_status' => 'pending_payment',
        ]);

        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/auth-token-123',
                    'reference' => 'WW-PAYSTACK001-123456',
                ],
            ], 200),
            'https://api.paystack.co/transaction/verify/WW-PAYSTACK001-123456' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'amount' => 5000000, // 50000 NGN in kobo
                    'currency' => 'NGN',
                    'reference' => 'WW-PAYSTACK001-123456',
                ],
            ], 200),
        ]);

        $initRes = $this->postJson("/api/orders/{$order->id}/pay/paystack/init");
        $initRes->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'authorization_url' => 'https://checkout.paystack.com/auth-token-123',
                'reference' => 'WW-PAYSTACK001-123456',
            ]);

        $webhookPayload = json_encode([
            'event' => 'charge.success',
            'data' => [
                'reference' => 'WW-PAYSTACK001-123456',
            ],
        ]);

        $signature = hash_hmac('sha512', $webhookPayload, $secretKey);

        $webhookRes = $this->call(
            'POST',
            '/api/webhooks/paystack',
            [],
            [],
            [],
            [
                'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $webhookPayload
        );

        $webhookRes->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'order_status' => 'paid',
        ]);
    }
}
