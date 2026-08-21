<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_health_endpoint_returns_service_status(): void
    {
        $this->getJson('/api/health')->assertOk()
            ->assertExactJson(['status' => 'ok', 'service' => 'MiniPOS API']);
    }

    public function test_category_crud_endpoints(): void
    {
        $category = $this->postJson('/api/categories', [
            'name' => 'Coffee', 'slug' => 'coffee', 'description' => 'Hot drinks',
        ])->assertCreated()->assertJsonPath('name', 'Coffee')->json();

        $this->getJson('/api/categories?search=Coffee')->assertOk()
            ->assertJsonPath('data.0.id', $category['id'])
            ->assertJsonPath('data.0.products_count', 0);
        $this->getJson("/api/categories/{$category['id']}")->assertOk()
            ->assertJsonPath('slug', 'coffee');
        $this->putJson("/api/categories/{$category['id']}", ['name' => 'Iced Coffee'])
            ->assertOk()->assertJsonPath('name', 'Iced Coffee');
        $this->deleteJson("/api/categories/{$category['id']}")->assertNoContent();
        $this->assertDatabaseMissing('categories', ['id' => $category['id']]);
    }

    public function test_category_validation_and_delete_conflict(): void
    {
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        Product::create(['category_id' => $category->id, 'name' => 'Latte', 'sku' => 'LATTE', 'price' => 4]);

        $this->postJson('/api/categories', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'slug']);
        $this->deleteJson("/api/categories/{$category->id}")->assertConflict();
    }

    public function test_customer_crud_endpoints(): void
    {
        $customer = $this->postJson('/api/customers', [
            'name' => 'Sovith Yea', 'email' => 'sovith@example.com', 'phone' => '012345678',
        ])->assertCreated()->json();

        $this->getJson('/api/customers?search=012345678')->assertOk()
            ->assertJsonPath('data.0.id', $customer['id']);
        $this->getJson("/api/customers/{$customer['id']}")->assertOk()
            ->assertJsonPath('email', 'sovith@example.com');
        $this->patchJson("/api/customers/{$customer['id']}", ['address' => 'Phnom Penh'])
            ->assertOk()->assertJsonPath('address', 'Phnom Penh');
        $this->deleteJson("/api/customers/{$customer['id']}")->assertNoContent();
        $this->assertDatabaseMissing('customers', ['id' => $customer['id']]);
    }

    public function test_customer_validation_rejects_duplicate_contact_details(): void
    {
        Customer::create(['name' => 'First', 'email' => 'same@example.com', 'phone' => '123']);

        $this->postJson('/api/customers', [
            'name' => 'Second', 'email' => 'same@example.com', 'phone' => '123',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'phone']);
    }

    public function test_product_crud_endpoints_and_filters(): void
    {
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $product = $this->postJson('/api/products', [
            'category_id' => $category->id, 'name' => 'Latte', 'sku' => 'LAT-1',
            'price' => 4.5, 'cost' => 2, 'stock' => 20, 'is_active' => true,
        ])->assertCreated()->assertJsonPath('category.name', 'Coffee')->json();

        $this->getJson("/api/products?search=LAT-1&category_id={$category->id}&active_only=1")
            ->assertOk()->assertJsonPath('data.0.id', $product['id']);
        $this->getJson("/api/products/{$product['id']}")->assertOk()
            ->assertJsonPath('name', 'Latte');
        $this->patchJson("/api/products/{$product['id']}", ['price' => 5, 'stock' => 18])
            ->assertOk()->assertJsonPath('price', '5.00')->assertJsonPath('stock', 18);
        $this->deleteJson("/api/products/{$product['id']}")->assertNoContent();
    }

    public function test_product_validation_rejects_invalid_data(): void
    {
        $this->postJson('/api/products', ['price' => -1])->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'name', 'sku', 'price']);
    }

    public function test_order_list_show_cancel_and_repeat_cancel_endpoints(): void
    {
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'sku' => 'ORD-1', 'price' => 5, 'stock' => 10]);
        $order = $this->postJson('/api/orders', [
            'payment_method' => 'cash', 'discount' => 1,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertCreated()->json();

        $this->getJson('/api/orders?status=completed&per_page=10')->assertOk()
            ->assertJsonPath('data.0.id', $order['id'])->assertJsonPath('data.0.items_count', 1);
        $this->getJson("/api/orders/{$order['id']}")->assertOk()
            ->assertJsonPath('items.0.product_name', 'Latte');
        $this->patchJson("/api/orders/{$order['id']}/cancel")->assertOk()
            ->assertJsonPath('status', 'cancelled')->assertJsonPath('payment_status', 'refunded');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 10]);
        $this->patchJson("/api/orders/{$order['id']}/cancel")->assertConflict();
    }

    public function test_order_endpoints_validate_payload_and_filters(): void
    {
        $this->postJson('/api/orders', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_method', 'items']);
        $this->getJson('/api/orders?date_from=2026-08-20&date_to=2026-08-19')
            ->assertUnprocessable()->assertJsonValidationErrors('date_to');
    }

    public function test_dashboard_endpoint_returns_aggregates(): void
    {
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'sku' => 'DASH-1', 'price' => 5, 'stock' => 5]);
        $this->postJson('/api/orders', ['payment_method' => 'cash', 'items' => [['product_id' => $product->id, 'quantity' => 1]]])->assertCreated();

        $this->getJson('/api/dashboard')->assertOk()
            ->assertJsonPath('today.orders', 1)
            ->assertJsonPath('today.sales', 5.5)
            ->assertJsonPath('popular_products.0.product_name', 'Latte');
    }

    public function test_report_endpoints_validate_date_filters(): void
    {
        foreach (['summary', 'products', 'customers'] as $report) {
            $this->getJson("/api/reports/{$report}?date_from=invalid")
                ->assertUnprocessable()->assertJsonValidationErrors('date_from');
        }
    }

    public function test_payway_endpoints_reject_non_qr_orders_and_disabled_simulation(): void
    {
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'sku' => 'PAY-1', 'price' => 5, 'stock' => 10]);
        $order = $this->postJson('/api/orders', ['payment_method' => 'cash', 'items' => [['product_id' => $product->id, 'quantity' => 1]]])->assertCreated()->json();

        $this->getJson("/api/orders/{$order['id']}/payway-payment")->assertNotFound();
        $this->postJson("/api/orders/{$order['id']}/payway-payment/simulate")->assertForbidden();
    }
}
