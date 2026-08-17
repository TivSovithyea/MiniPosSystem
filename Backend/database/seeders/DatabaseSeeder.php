<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@minipos.test'], ['name' => 'MiniPOS Admin', 'password' => 'password']);

        $categories = collect([
            ['Coffee', 'coffee'], ['Bakery', 'bakery'], ['Tea', 'tea'], ['Food', 'food'],
            ['Cold Drinks', 'cold-drinks'], ['Desserts', 'desserts'], ['Snacks', 'snacks'], ['Retail', 'retail'],
        ])->mapWithKeys(function (array $item) {
            $category = Category::updateOrCreate(['slug' => $item[1]], ['name' => $item[0], 'is_active' => true]);

            return [$item[1] => $category];
        });

        $catalog = [
            ['coffee', 'Iced Americano', 'COF-001', 3.50, 1.20, '☕'], ['coffee', 'Cappuccino', 'COF-002', 4.00, 1.45, '☕'],
            ['coffee', 'Caramel Latte', 'COF-003', 4.75, 1.75, '🥛'], ['coffee', 'Espresso', 'COF-004', 2.50, 0.80, '☕'],
            ['bakery', 'Butter Croissant', 'BAK-001', 2.75, 1.00, '🥐'], ['bakery', 'Blueberry Muffin', 'BAK-002', 3.25, 1.20, '🧁'],
            ['bakery', 'Cinnamon Roll', 'BAK-003', 3.50, 1.30, '🥮'], ['tea', 'Matcha Latte', 'TEA-001', 4.25, 1.60, '🍵'],
            ['tea', 'Thai Milk Tea', 'TEA-002', 3.75, 1.25, '🧋'], ['tea', 'Lemon Iced Tea', 'TEA-003', 3.00, 0.90, '🍋'],
            ['food', 'Club Sandwich', 'FOD-001', 6.50, 2.80, '🥪'], ['food', 'Chicken Wrap', 'FOD-002', 6.25, 2.60, '🌯'],
            ['food', 'Caesar Salad', 'FOD-003', 5.75, 2.30, '🥗'], ['food', 'Beef Burger', 'FOD-004', 7.50, 3.20, '🍔'],
            ['cold-drinks', 'Berry Smoothie', 'DRK-001', 4.75, 1.90, '🥤'], ['cold-drinks', 'Mango Smoothie', 'DRK-002', 4.50, 1.80, '🥭'],
            ['cold-drinks', 'Sparkling Water', 'DRK-003', 2.00, 0.60, '💧'], ['desserts', 'Chocolate Cake', 'DES-001', 3.90, 1.50, '🍰'],
            ['desserts', 'Cheesecake', 'DES-002', 4.25, 1.70, '🍰'], ['desserts', 'Vanilla Ice Cream', 'DES-003', 3.25, 1.10, '🍨'],
            ['snacks', 'French Fries', 'SNK-001', 3.50, 1.20, '🍟'], ['snacks', 'Nachos', 'SNK-002', 4.00, 1.45, '🧀'],
            ['retail', 'Coffee Beans 250g', 'RTL-001', 12.00, 6.00, '🫘'], ['retail', 'MiniPOS Tumbler', 'RTL-002', 15.00, 7.50, '🥤'],
        ];

        $products = collect($catalog)->map(function (array $item, int $index) use ($categories) {
            return Product::updateOrCreate(['sku' => $item[2]], [
                'category_id' => $categories[$item[0]]->id, 'name' => $item[1], 'price' => $item[3],
                'cost' => $item[4], 'stock' => 30 + (($index * 17) % 120), 'emoji' => $item[5], 'is_active' => true,
            ]);
        });

        $customers = collect(range(1, 40))->map(function (int $number) {
            return Customer::updateOrCreate(['email' => sprintf('customer%02d@minipos.test', $number)], [
                'name' => fake()->name(), 'phone' => sprintf('012%06d', 100000 + $number),
                'address' => fake()->streetAddress().', '.fake()->city(),
            ]);
        });

        Order::where('order_number', 'like', 'DEMO-%')->delete();
        foreach (range(1, 180) as $sequence) {
            $orderedAt = now()->subDays(($sequence * 7) % 60)->setTime(7 + ($sequence % 14), ($sequence * 13) % 60);
            $selectedProducts = $products->shuffle()->take(1 + ($sequence % 4));
            $lines = $selectedProducts->map(function (Product $product, int $index) use ($sequence) {
                $quantity = 1 + (($sequence + $index) % 4);

                return ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => $product->price, 'quantity' => $quantity, 'line_total' => round((float) $product->price * $quantity, 2)];
            });
            $subtotal = round($lines->sum('line_total'), 2);
            $discount = $sequence % 9 === 0 ? round($subtotal * 0.05, 2) : 0;
            $tax = round(($subtotal - $discount) * 0.10, 2);
            $cancelled = $sequence % 13 === 0;
            $order = Order::create([
                'order_number' => sprintf('DEMO-%s-%04d', $orderedAt->format('Ymd'), $sequence),
                'customer_id' => $sequence % 5 === 0 ? null : $customers[($sequence * 3) % $customers->count()]->id,
                'subtotal' => $subtotal, 'discount' => $discount, 'tax' => $tax, 'total' => $subtotal - $discount + $tax,
                'payment_method' => ['cash', 'card', 'qr'][$sequence % 3], 'payment_status' => $cancelled ? 'refunded' : 'paid',
                'status' => $cancelled ? 'cancelled' : 'completed', 'notes' => $sequence % 11 === 0 ? 'Demo order with a customer note.' : null,
                'ordered_at' => $orderedAt,
            ]);
            $order->items()->createMany($lines->all());
        }
    }
}
