<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Admin User
        |--------------------------------------------------------------------------
        */
        User::updateOrCreate(
            [
                'email' => 'admin@minipos.test',
            ],
            [
                'name' => 'MiniPOS Admin',
                'password' => Hash::make('password'),
            ]
        );

        foreach (range(1, 20) as $number) {
            User::updateOrCreate(
                ['email' => sprintf('user%02d@minipos.test', $number)],
                [
                    'name' => sprintf('MiniPOS User %02d', $number),
                    'password' => Hash::make('password'),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */
        $categories = collect([
            ['Coffee & Hot Drinks', 'coffee'],
            ['Bakery & Bread', 'bakery'],
            ['Tea & Ready-to-Drink', 'tea'],
            ['Instant & Ready Meals', 'food'],
            ['Water & Soft Drinks', 'cold-drinks'],
            ['Dairy & Desserts', 'desserts'],
            ['Snacks & Confectionery', 'snacks'],
            ['Household & General', 'retail'],
            ['Rice, Noodles & Staples', 'staples'],
            ['Cooking & Condiments', 'cooking'],
            ['Personal Care', 'personal-care'],
            ['Frozen & Chilled Food', 'frozen-chilled'],
            ['Baby Care', 'baby-care'],
            ['Automotive & Travel', 'automotive-travel'],
            ['Beer, Wine & Spirits', 'beer-wine-spirits'],
        ])->mapWithKeys(function (array $item) {

            $category = Category::updateOrCreate(
                [
                    'slug' => $item[1],
                ],
                [
                    'name' => $item[0],
                    'is_active' => true,
                ]
            );

            return [
                $item[1] => $category,
            ];
        });

        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        */
        $catalog = [
            [
                'coffee',
                'Iced Americano',
                'COF-001',
                3.50,
                1.20,
                '☕',
            ],
            [
                'coffee',
                'Cappuccino',
                'COF-002',
                4.00,
                1.45,
                '☕',
            ],
            [
                'coffee',
                'Caramel Latte',
                'COF-003',
                4.75,
                1.75,
                '🥛',
            ],
            [
                'coffee',
                'Espresso',
                'COF-004',
                2.50,
                0.80,
                '☕',
            ],

            [
                'bakery',
                'Butter Croissant',
                'BAK-001',
                2.75,
                1.00,
                '🥐',
            ],
            [
                'bakery',
                'Blueberry Muffin',
                'BAK-002',
                3.25,
                1.20,
                '🧁',
            ],
            [
                'bakery',
                'Cinnamon Roll',
                'BAK-003',
                3.50,
                1.30,
                '🥮',
            ],

            [
                'tea',
                'Matcha Latte',
                'TEA-001',
                4.25,
                1.60,
                '🍵',
            ],
            [
                'tea',
                'Thai Milk Tea',
                'TEA-002',
                3.75,
                1.25,
                '🧋',
            ],
            [
                'tea',
                'Lemon Iced Tea',
                'TEA-003',
                3.00,
                0.90,
                '🍋',
            ],

            [
                'food',
                'Club Sandwich',
                'FOD-001',
                6.50,
                2.80,
                '🥪',
            ],
            [
                'food',
                'Chicken Wrap',
                'FOD-002',
                6.25,
                2.60,
                '🌯',
            ],
            [
                'food',
                'Caesar Salad',
                'FOD-003',
                5.75,
                2.30,
                '🥗',
            ],
            [
                'food',
                'Beef Burger',
                'FOD-004',
                7.50,
                3.20,
                '🍔',
            ],

            [
                'cold-drinks',
                'Berry Smoothie',
                'DRK-001',
                4.75,
                1.90,
                '🥤',
            ],
            [
                'cold-drinks',
                'Mango Smoothie',
                'DRK-002',
                4.50,
                1.80,
                '🥭',
            ],
            [
                'cold-drinks',
                'Sparkling Water',
                'DRK-003',
                2.00,
                0.60,
                '💧',
            ],

            [
                'desserts',
                'Chocolate Cake',
                'DES-001',
                3.90,
                1.50,
                '🍰',
            ],
            [
                'desserts',
                'Cheesecake',
                'DES-002',
                4.25,
                1.70,
                '🍰',
            ],
            [
                'desserts',
                'Vanilla Ice Cream',
                'DES-003',
                3.25,
                1.10,
                '🍨',
            ],

            [
                'snacks',
                'French Fries',
                'SNK-001',
                3.50,
                1.20,
                '🍟',
            ],
            [
                'snacks',
                'Nachos',
                'SNK-002',
                4.00,
                1.45,
                '🧀',
            ],

            [
                'retail',
                'Coffee Beans 250g',
                'RTL-001',
                12.00,
                6.00,
                '🫘',
            ],
            [
                'retail',
                'MiniPOS Tumbler',
                'RTL-002',
                15.00,
                7.50,
                '🥤',
            ],
        ];

        $products = collect($catalog)->map(
            function (array $item, int $index) use ($categories) {

                return Product::updateOrCreate(
                    [
                        'sku' => $item[2],
                    ],
                    [
                        'category_id' => $categories[$item[0]]->id,
                        'name' => $item[1],
                        'price' => $item[3],
                        'cost' => $item[4],
                        'stock' => 30 + (($index * 17) % 120),
                        'is_active' => true,
                    ]
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Cambodia Convenience-Mart Demo Catalog
        |--------------------------------------------------------------------------
        |
        | These are representative demo records inspired by the mix normally found
        | in Cambodian fuel-station marts. They are not TELA's official live SKUs
        | or prices. Two package variants for eight items across fifteen categories
        | add 240 products; together with the core catalog this seeds 264 products.
        |
        */
        $martCatalog = [
            'coffee' => ['Cambodia Arabica Coffee', 'Kampot Robusta Coffee', 'Brown Coffee Can', 'Birdy Coffee', 'Nescafe Latte', 'Nescafe Espresso', 'OldTown White Coffee', 'Cafe Amazon Mocha'],
            'bakery' => ['Fresh Milk Bread', 'Whole Wheat Bread', 'Pandan Bun', 'Red Bean Bun', 'Chocolate Croissant', 'Butter Cake', 'Banana Cake', 'Mini Swiss Roll'],
            'tea' => ['Oishi Green Tea', 'Ichitan Honey Lemon Tea', 'Fuze Tea Lemon', 'Pokka Jasmine Tea', 'Lipton Ice Tea', 'Cambodia Iced Tea', 'Soy Milk Original', 'Chocolate Malt Drink'],
            'food' => ['Mama Tom Yum Noodles', 'Mama Pork Noodles', 'Indomie Mi Goreng', 'Samyang Hot Chicken Noodles', 'Rice Porridge Cup', 'Tuna Sandwich', 'Chicken Burger', 'Khmer Pork Rice Box'],
            'cold-drinks' => ['Vital Premium Water', 'KulEN Pure Water', 'Cambodia Water', 'Coca-Cola Original', 'Pepsi Original', 'Sprite Lemon Lime', 'Fanta Orange', 'Red Bull Energy Drink'],
            'desserts' => ['Dutch Mill Yoghurt', 'Meiji Fresh Milk', 'Foremost Chocolate Milk', 'Wall’s Vanilla Ice Cream', 'Wall’s Chocolate Ice Cream', 'Mochi Ice Cream', 'Caramel Pudding', 'Fruit Jelly Cup'],
            'snacks' => ['Hanami Prawn Crackers', 'Lay’s Original Chips', 'Lay’s Nori Seaweed Chips', 'Jack n Jill Roller Coaster', 'Pocky Chocolate', 'Oreo Original', 'Dried Mango', 'Roasted Cashew Nuts'],
            'retail' => ['Cellox Facial Tissue', 'Scott Toilet Tissue', 'Sunlight Dishwashing Liquid', 'Comfort Fabric Softener', 'Attack Laundry Powder', 'Baygon Insect Spray', 'Garbage Bags', 'AA Alkaline Batteries'],
            'staples' => ['Phka Rumduol Rice', 'Jasmine Rice', 'Brown Rice', 'Rice Vermicelli', 'Dried Egg Noodles', 'Instant Rice Noodles', 'Rolled Oats', 'All-Purpose Flour'],
            'cooking' => ['Fish Sauce', 'Oyster Sauce', 'Light Soy Sauce', 'Chili Sauce', 'Tomato Ketchup', 'Palm Sugar', 'Iodized Salt', 'Vegetable Cooking Oil'],
            'personal-care' => ['Colgate Toothpaste', 'Oral-B Toothbrush', 'Dove Body Wash', 'Lifebuoy Hand Wash', 'Sunsilk Shampoo', 'Pantene Conditioner', 'Nivea Deodorant', 'Wet Wipes'],
            'frozen-chilled' => ['CP Chicken Sausage', 'CP Pork Sausage', 'Frozen Fish Balls', 'Frozen Beef Balls', 'French Fries', 'Chicken Nuggets', 'Crab Sticks', 'Frozen Dumplings'],
            'baby-care' => ['MamyPoko Diapers', 'Huggies Dry Diapers', 'Kodomo Baby Powder', 'Johnson’s Baby Wash', 'Baby Wet Wipes', 'Cerelac Rice Cereal', 'UHT Growing-Up Milk', 'Baby Feeding Bottle'],
            'automotive-travel' => ['Engine Oil 10W-40', 'Coolant Premix', 'Windshield Washer Fluid', 'Microfiber Cleaning Cloth', 'Car Air Freshener', 'USB Car Charger', 'Disposable Raincoat', 'Travel Neck Pillow'],
            'beer-wine-spirits' => ['Cambodia Beer', 'Angkor Premium Beer', 'Ganzberg Beer', 'Hanuman Beer', 'ABC Extra Stout', 'Tiger Crystal', 'Singha Beer', 'Red Wine Selection'],
        ];

        $packageVariants = [
            ['Small', 1.00],
            ['Large', 1.65],
        ];
        $martProducts = collect();
        $skuNumber = 1;

        foreach ($martCatalog as $slug => $names) {
            foreach ($names as $itemIndex => $name) {
                foreach ($packageVariants as [$size, $multiplier]) {
                    $price = round((0.75 + (($skuNumber * 17) % 925) / 100) * $multiplier, 2);
                    $product = Product::updateOrCreate(
                        ['sku' => sprintf('TM-%04d', $skuNumber)],
                        [
                            'category_id' => $categories[$slug]->id,
                            'name' => $name.' - '.$size,
                            'description' => 'Demo convenience-mart product for Cambodia retail POS testing.',
                            'price' => $price,
                            'cost' => round($price * 0.72, 2),
                            'stock' => 20 + (($skuNumber * 23) % 181),
                            'is_active' => $skuNumber % 29 !== 0,
                        ]
                    );
                    $martProducts->push($product);
                    $skuNumber++;
                }
            }
        }

        $products = $products->concat($martProducts);

        /*
        |--------------------------------------------------------------------------
        | Customers
        |--------------------------------------------------------------------------
        |
        | We don't use fake() because Faker is usually installed
        | as a development dependency and is unavailable on Render
        | when Composer runs with --no-dev.
        |
        */
        $customerNames = [
            'Dara Sok',
            'Sophea Chan',
            'Vanna Kim',
            'Srey Mom',
            'Ratha Lim',
            'Piseth Heng',
            'Bopha Keo',
            'Sokha Chhay',
            'Vuthy Kong',
            'Sreypov Mao',
        ];

        $areas = [
            'Sen Sok, Phnom Penh',
            'Toul Kork, Phnom Penh',
            'Boeung Keng Kang, Phnom Penh',
            'Chamkarmon, Phnom Penh',
            'Chroy Changvar, Phnom Penh',
            'Meanchey, Phnom Penh',
            'Russey Keo, Phnom Penh',
            'Daun Penh, Phnom Penh',
            'Por Senchey, Phnom Penh',
            '7 Makara, Phnom Penh',
        ];

        $customers = collect(range(1, 40))->map(
            function (int $number) use ($customerNames, $areas) {

                $nameIndex = ($number - 1) % count($customerNames);
                $areaIndex = ($number - 1) % count($areas);

                return Customer::updateOrCreate(
                    [
                        'email' => sprintf(
                            'customer%02d@minipos.test',
                            $number
                        ),
                    ],
                    [
                        'name' => $customerNames[$nameIndex]
                            . ' '
                            . $number,

                        'phone' => sprintf(
                            '012%06d',
                            100000 + $number
                        ),

                        'address' => sprintf(
                            'Street %d, %s',
                            100 + $number,
                            $areas[$areaIndex]
                        ),
                    ]
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Remove Previous Demo Orders
        |--------------------------------------------------------------------------
        |
        | Delete order items first to avoid foreign-key errors if your
        | database does not use cascadeOnDelete().
        |
        */
        $demoOrders = Order::where(
            'order_number',
            'like',
            'DEMO-%'
        )->get();

        foreach ($demoOrders as $demoOrder) {
            $demoOrder->items()->delete();
            $demoOrder->delete();
        }

        /*
        |--------------------------------------------------------------------------
        | Demo Orders
        |--------------------------------------------------------------------------
        */
        foreach (range(1, 180) as $sequence) {

            $orderedAt = now()
                ->subDays(($sequence * 7) % 60)
                ->setTime(
                    7 + ($sequence % 14),
                    ($sequence * 13) % 60
                );

            /*
            |--------------------------------------------------------------------------
            | Select Products
            |--------------------------------------------------------------------------
            */
            $selectedProducts = $products
                ->shuffle()
                ->take(1 + ($sequence % 4));

            /*
            |--------------------------------------------------------------------------
            | Order Items
            |--------------------------------------------------------------------------
            */
            $lines = $selectedProducts->map(
                function (
                    Product $product,
                    int $index
                ) use ($sequence) {

                    $quantity =
                        1 + (($sequence + $index) % 4);

                    $unitPrice =
                        (float) $product->price;

                    return [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'unit_price' => $unitPrice,
                        'quantity' => $quantity,
                        'line_total' => round(
                            $unitPrice * $quantity,
                            2
                        ),
                    ];
                }
            );

            /*
            |--------------------------------------------------------------------------
            | Calculate Totals
            |--------------------------------------------------------------------------
            */
            $subtotal = round(
                $lines->sum('line_total'),
                2
            );

            $discount =
                $sequence % 9 === 0
                    ? round($subtotal * 0.05, 2)
                    : 0;

            $tax = round(
                ($subtotal - $discount) * 0.10,
                2
            );

            $total = round(
                $subtotal - $discount + $tax,
                2
            );

            /*
            |--------------------------------------------------------------------------
            | Order Status
            |--------------------------------------------------------------------------
            */
            $cancelled =
                $sequence % 13 === 0;

            /*
            |--------------------------------------------------------------------------
            | Customer
            |--------------------------------------------------------------------------
            |
            | Every 5th order is walk-in / no customer.
            |
            */
            $customerId =
                $sequence % 5 === 0
                    ? null
                    : $customers[
                        ($sequence * 3)
                        % $customers->count()
                    ]->id;

            /*
            |--------------------------------------------------------------------------
            | Create Order
            |--------------------------------------------------------------------------
            */
            $order = Order::create([
                'order_number' => sprintf(
                    'DEMO-%s-%04d',
                    $orderedAt->format('Ymd'),
                    $sequence
                ),

                'customer_id' => $customerId,

                'subtotal' => $subtotal,

                'discount' => $discount,

                'tax' => $tax,

                'total' => $total,

                'payment_method' => [
                    'cash',
                    'card',
                    'qr',
                ][$sequence % 3],

                'payment_status' =>
                    $cancelled
                        ? 'refunded'
                        : 'paid',

                'status' =>
                    $cancelled
                        ? 'cancelled'
                        : 'completed',

                'notes' =>
                    $sequence % 11 === 0
                        ? 'Demo order with a customer note.'
                        : null,

                'ordered_at' => $orderedAt,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Create Order Items
            |--------------------------------------------------------------------------
            */
            $order
                ->items()
                ->createMany(
                    $lines->all()
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Console Message
        |--------------------------------------------------------------------------
        */
        $this->command?->info(
            'MiniPOS database seeded successfully.'
        );

        $this->command?->info(
            'Admin Email: admin@minipos.test'
        );

        $this->command?->info(
            'Admin Password: password'
        );
    }
}
