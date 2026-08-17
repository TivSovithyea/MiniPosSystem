<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $completedToday = Order::where('status', 'completed')->whereDate('ordered_at', today());

        return response()->json([
            'today' => ['sales' => (float) (clone $completedToday)->sum('total'), 'orders' => (clone $completedToday)->count()],
            'customers' => Customer::count(), 'low_stock' => Product::where('is_active', true)->where('stock', '<=', 10)->count(),
            'popular_products' => OrderItem::selectRaw('product_id, product_name, SUM(quantity) as quantity_sold')->groupBy('product_id', 'product_name')->orderByDesc('quantity_sold')->limit(5)->get(),
            'recent_orders' => Order::with('customer:id,name')->latest('ordered_at')->limit(5)->get(),
        ]);
    }
}
