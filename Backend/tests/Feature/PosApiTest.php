<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_products_can_be_listed(): void
    {
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        Product::create(['category_id' => $category->id, 'name' => 'Americano', 'sku' => 'COF-1', 'price' => 3.5, 'stock' => 10]);
        $this->getJson('/api/products')->assertOk()->assertJsonPath('data.0.name', 'Americano');
    }

    public function test_checkout_uses_database_price_and_decrements_stock(): void
    {
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Americano', 'sku' => 'COF-1', 'price' => 3.5, 'stock' => 10]);
        $this->postJson('/api/orders', ['payment_method' => 'cash', 'items' => [['product_id' => $product->id, 'quantity' => 2]]])
            ->assertCreated()->assertJsonPath('subtotal', '7.00')->assertJsonPath('tax', '0.70')->assertJsonPath('total', '7.70');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 8]);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'quantity' => 2, 'line_total' => 7]);
    }

    public function test_checkout_rejects_insufficient_stock(): void
    {
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Americano', 'sku' => 'COF-1', 'price' => 3.5, 'stock' => 1]);
        $this->postJson('/api/orders', ['payment_method' => 'cash', 'items' => [['product_id' => $product->id, 'quantity' => 2]]])->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_payway_qr_checkout_stays_pending_until_payway_confirms_payment(): void
    {
        config([
            'services.payway.merchant_id' => 'sandbox-merchant',
            'services.payway.api_key' => 'sandbox-key',
            'services.payway.currency' => 'USD',
            'services.payway.callback_url' => 'https://api.example.com/api/payments/payway/callback',
        ]);
        Http::fake([
            '*/generate-qr' => Http::response([
                'qrString' => '000201010212-payway-qr',
                'qrImage' => 'data:image/png;base64,abc',
                'abapay_deeplink' => 'abamobilebank://payway',
                'status' => ['code' => '0', 'message' => 'Success.'],
            ]),
            '*/check-transaction-2' => Http::response([
                'status' => ['code' => '00', 'message' => 'Success.'],
                'data' => [
                    'payment_status_code' => 0,
                    'payment_status' => 'APPROVED',
                    'original_currency' => 'USD',
                    'original_amount' => 11,
                    'apv' => '123456',
                ],
            ]),
        ]);
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'sku' => 'QR-1', 'price' => 5, 'stock' => 10]);

        $order = $this->postJson('/api/orders', ['payment_method' => 'qr', 'items' => [['product_id' => $product->id, 'quantity' => 2]]])
            ->assertCreated()->assertJsonPath('payment_status', 'pending')->assertJsonPath('status', 'pending')
            ->assertJsonPath('payway_payment.currency', 'USD')->json();

        $this->assertSame('000201010212-payway-qr', $order['payway_payment']['qr_payload']);
        $this->assertSame('abamobilebank://payway', $order['payway_payment']['deeplink']);

        $this->getJson("/api/orders/{$order['id']}/payway-payment")
            ->assertOk()->assertJsonPath('status', 'paid');
        $this->assertDatabaseHas('orders', ['id' => $order['id'], 'status' => 'completed', 'payment_status' => 'paid']);
    }

    public function test_payway_callback_requires_a_valid_hmac_signature(): void
    {
        config(['services.payway.api_key' => 'sandbox-key']);
        $payload = json_encode(['tran_id' => 'POS-TEST', 'status' => '0']);

        $this->call('POST', '/api/payments/payway/callback', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYWAY_HMAC_SHA512' => 'invalid',
        ], $payload)->assertForbidden();
    }

    public function test_sales_reports_summarize_orders_products_and_customers(): void
    {
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'sku' => 'COF-2', 'price' => 5, 'stock' => 10]);
        $customer = Customer::create(['name' => 'Report Customer', 'email' => 'report@example.com']);
        $this->postJson('/api/orders', ['customer_id' => $customer->id, 'payment_method' => 'cash', 'items' => [['product_id' => $product->id, 'quantity' => 2]]])->assertCreated();

        $this->getJson('/api/reports/summary')->assertOk()
            ->assertJsonPath('totals.orders_count', 1)->assertJsonPath('totals.items_sold', 2)
            ->assertJsonPath('totals.revenue', 11);
        $this->getJson('/api/reports/products')->assertOk()
            ->assertJsonPath('data.0.name', 'Latte')->assertJsonPath('data.0.quantity_sold', 2);
        $this->getJson('/api/reports/customers')->assertOk()
            ->assertJsonPath('data.0.name', 'Report Customer')->assertJsonPath('data.0.orders_count', 1);
    }
}
