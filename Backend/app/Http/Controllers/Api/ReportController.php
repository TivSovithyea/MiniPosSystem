<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $this->validateFilters($request);
        $orders = $this->completedOrders($request);

        $totals = (clone $orders)->selectRaw('COUNT(*) as orders_count, COALESCE(SUM(subtotal), 0) as subtotal, COALESCE(SUM(discount), 0) as discount, COALESCE(SUM(tax), 0) as tax, COALESCE(SUM(total), 0) as revenue, COALESCE(AVG(total), 0) as average_order')->first();
        $itemsSold = DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'completed');
        $this->applyDates($itemsSold, $request, 'orders.ordered_at');

        return response()->json([
            'totals' => array_merge($totals->toArray(), ['items_sold' => (int) $itemsSold->sum('order_items.quantity')]),
            'payment_methods' => (clone $orders)->selectRaw('payment_method, COUNT(*) as orders_count, SUM(total) as revenue')->groupBy('payment_method')->orderByDesc('revenue')->get(),
            'daily_sales' => (clone $orders)->selectRaw('DATE(ordered_at) as date, COUNT(*) as orders_count, SUM(total) as revenue')->groupByRaw('DATE(ordered_at)')->orderBy('date')->get(),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $this->validateFilters($request);
        $query = DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('orders.status', 'completed')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($inner) => $inner->where('order_items.product_name', 'like', '%'.$request->string('search').'%')->orWhere('products.sku', 'like', '%'.$request->string('search').'%')));
        $this->applyDates($query, $request, 'orders.ordered_at');

        return response()->json($query->selectRaw('products.id, products.sku, order_items.product_name as name, categories.name as category_name, SUM(order_items.quantity) as quantity_sold, COUNT(DISTINCT orders.id) as orders_count, SUM(order_items.line_total) as revenue')
            ->groupBy('products.id', 'products.sku', 'order_items.product_name', 'categories.name')->orderByDesc('revenue')->paginate($this->perPage($request)));
    }

    public function customers(Request $request): JsonResponse
    {
        $this->validateFilters($request);
        $query = DB::table('orders')->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
            ->where('orders.status', 'completed')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($inner) => $inner->where('customers.name', 'like', '%'.$request->string('search').'%')->orWhere('customers.email', 'like', '%'.$request->string('search').'%')->orWhere('customers.phone', 'like', '%'.$request->string('search').'%')));
        $this->applyDates($query, $request, 'orders.ordered_at');

        return response()->json($query->selectRaw("customers.id, COALESCE(customers.name, 'Walk-in customers') as name, customers.email, customers.phone, COUNT(orders.id) as orders_count, SUM(orders.total) as revenue, AVG(orders.total) as average_order, MAX(orders.ordered_at) as last_order_at")
            ->groupBy('customers.id', 'customers.name', 'customers.email', 'customers.phone')->orderByDesc('revenue')->paginate($this->perPage($request)));
    }

    private function completedOrders(Request $request): Builder
    {
        $query = Order::query()->where('status', 'completed');
        $this->applyDates($query, $request, 'ordered_at');

        return $query;
    }

    private function applyDates($query, Request $request, string $column): void
    {
        $query->when($request->filled('date_from'), fn ($q) => $q->whereDate($column, '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate($column, '<=', $request->date('date_to')));
    }

    private function validateFilters(Request $request): void
    {
        $request->validate(['date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from'], 'search' => ['nullable', 'string', 'max:150'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
    }

    private function perPage(Request $request): int
    {
        return $request->integer('per_page', 20);
    }
}
