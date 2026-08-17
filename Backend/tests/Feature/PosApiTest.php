<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_sales_reports_summarize_orders_products_and_customers(): void
    {
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'sku' => 'COF-2', 'price' => 5, 'stock' => 10]);
        $customer = \App\Models\Customer::create(['name' => 'Report Customer', 'email' => 'report@example.com']);
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
