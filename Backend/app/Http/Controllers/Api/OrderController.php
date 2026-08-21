<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\PaywayPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(['date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);

        return response()->json(Order::with('customer:id,name')->withCount('items')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('ordered_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('ordered_at', '<=', $request->date('date_to')))
            ->latest('ordered_at')->paginate($request->integer('per_page', 20)));
    }

    public function store(Request $request, PaywayPaymentService $payway): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'], 'payment_method' => ['required', 'in:cash,card,qr'],
            'discount' => ['sometimes', 'numeric', 'min:0'], 'notes' => ['nullable', 'string', 'max:1000'], 'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'], 'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $order = DB::transaction(function () use ($data) {
            $subtotal = 0;
            $lines = [];
            foreach ($data['items'] as $item) {
                $product = Product::whereKey($item['product_id'])->lockForUpdate()->firstOrFail();
                if (! $product->is_active || $product->stock < $item['quantity']) {
                    throw ValidationException::withMessages(['items' => "{$product->name} has only {$product->stock} item(s) available."]);
                }
                $lineTotal = round((float) $product->price * $item['quantity'], 2);
                $subtotal += $lineTotal;
                $lines[] = ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => $product->price, 'quantity' => $item['quantity'], 'line_total' => $lineTotal];
                $product->decrement('stock', $item['quantity']);
            }
            $discount = min((float) ($data['discount'] ?? 0), $subtotal);
            $tax = round(($subtotal - $discount) * 0.10, 2);
            $isQr = $data['payment_method'] === 'qr';
            $order = Order::create(['order_number' => 'POS-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)), 'customer_id' => $data['customer_id'] ?? null, 'subtotal' => $subtotal, 'tax' => $tax, 'discount' => $discount, 'total' => $subtotal - $discount + $tax, 'payment_method' => $data['payment_method'], 'payment_status' => $isQr ? 'pending' : 'paid', 'status' => $isQr ? 'pending' : 'completed', 'notes' => $data['notes'] ?? null, 'ordered_at' => now()]);
            $order->items()->createMany($lines);

            return $order;
        });

        if ($order->payment_method === 'qr') {
            try {
                $payway->create($order);
            } catch (\Throwable $exception) {
                DB::transaction(function () use ($order) {
                    foreach ($order->items as $item) {
                        Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                    }
                    $order->delete();
                });
                throw $exception;
            }
        }

        return response()->json($order->load(['items.product:id,sku', 'customer:id,name', 'paywayPayment']), 201);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json($order->load(['items.product:id,sku', 'customer']));
    }

    public function cancel(Order $order, PaywayPaymentService $payway): JsonResponse
    {
        if ($order->status === 'cancelled') {
            return response()->json(['message' => 'Order is already cancelled.'], 409);
        }
        if ($order->payment_method === 'qr' && $order->payment_status === 'pending' && $order->paywayPayment) {
            if (! $payway->cancel($order->paywayPayment)) {
                return response()->json(['message' => 'Payment was already approved and the order cannot be cancelled as unpaid.'], 409);
            }
        }
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                Product::whereKey($item->product_id)->increment('stock', $item->quantity);
            }
            $order->update(['status' => 'cancelled', 'payment_status' => $order->payment_status === 'paid' ? 'refunded' : 'cancelled']);
            $order->paywayPayment?->update(['status' => 'cancelled']);
        });

        return response()->json($order->fresh()->load('items'));
    }
}
