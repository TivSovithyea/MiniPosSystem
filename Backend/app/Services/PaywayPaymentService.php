<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaywayPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaywayPaymentService
{
    public function create(Order $order): PaywayPayment
    {
        $merchantId = (string) config('services.payway.merchant_id');
        $apiKey = (string) config('services.payway.api_key');
        if ($merchantId === '' || $apiKey === '') {
            throw new RuntimeException('PAYWAY_MERCHANT_ID and PAYWAY_API_KEY must be configured.');
        }

        $order->loadMissing(['items', 'customer']);
        $reqTime = now('UTC')->format('YmdHis');
        $amount = number_format((float) $order->total, 2, '.', '');
        $currency = strtoupper((string) config('services.payway.currency', 'USD'));
        $paymentOption = (string) config('services.payway.payment_option', 'abapay_khqr');
        $lifetime = max(3, min((int) config('services.payway.qr_lifetime_minutes', 10), 1440));
        $items = base64_encode(json_encode($order->items->map(fn ($item) => [
            'name' => $item->product_name,
            'quantity' => $item->quantity,
            'price' => (float) $item->unit_price,
        ])->values(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $callbackUrl = base64_encode((string) config('services.payway.callback_url'));

        $payload = [
            'req_time' => $reqTime,
            'merchant_id' => $merchantId,
            'tran_id' => $order->order_number,
            'first_name' => $order->customer?->name ?? 'Walk-in',
            'last_name' => '',
            'email' => $order->customer?->email ?? '',
            'phone' => $order->customer?->phone ?? '',
            'amount' => $amount,
            'purchase_type' => 'purchase',
            'payment_option' => $paymentOption,
            'items' => $items,
            'currency' => $currency,
            'callback_url' => $callbackUrl,
            'return_params' => $order->order_number,
            'lifetime' => $lifetime,
            'qr_image_template' => 'template3_color',
        ];
        $payload['hash'] = $this->sign([
            $reqTime, $merchantId, $order->order_number, $amount, $items,
            $payload['first_name'], '', $payload['email'], $payload['phone'],
            'purchase', $paymentOption, $callbackUrl, $currency,
            $order->order_number, (string) $lifetime, 'template3_color',
        ]);

        $response = Http::acceptJson()->asJson()->timeout(15)
            ->post($this->url('/api/payment-gateway/v1/payments/generate-qr'), $payload)
            ->throw()->json();
        $code = (string) data_get($response, 'status.code', '');
        if (! in_array($code, ['0', '00'], true) || empty($response['qrString'] ?? $response['qr_string'] ?? null)) {
            throw new RuntimeException((string) data_get($response, 'status.message', 'PayWay could not generate the payment QR.'));
        }
        $storedResponse = $response;
        unset($storedResponse['qrImage'], $storedResponse['qr_image']);

        return $order->paywayPayment()->create([
            'reference' => $order->order_number,
            'qr_payload' => $response['qrString'] ?? $response['qr_string'],
            'qr_image' => $response['qrImage'] ?? $response['qr_image'] ?? null,
            'deeplink' => $response['abapay_deeplink'] ?? null,
            'currency' => $currency,
            'amount' => $order->total,
            'expires_at' => now()->addMinutes($lifetime),
            'provider_response' => $storedResponse,
        ]);
    }

    public function check(PaywayPayment $payment): PaywayPayment
    {
        if ($payment->status !== 'pending') {
            return $payment;
        }

        $reqTime = now('UTC')->format('YmdHis');
        $merchantId = (string) config('services.payway.merchant_id');
        $payload = [
            'req_time' => $reqTime,
            'merchant_id' => $merchantId,
            'tran_id' => $payment->reference,
            'hash' => $this->sign([$reqTime, $merchantId, $payment->reference]),
        ];
        $response = Http::acceptJson()->asJson()->timeout(10)
            ->post($this->url('/api/payment-gateway/v1/payments/check-transaction-2'), $payload)
            ->throw()->json();
        $data = $response['data'] ?? [];

        if (strtoupper((string) ($data['payment_status'] ?? '')) === 'APPROVED' && (int) ($data['payment_status_code'] ?? -1) === 0) {
            return $this->markPaid($payment, $data, $response);
        }
        if ($payment->expires_at->isPast()) {
            $payment->update(['status' => 'expired', 'provider_response' => $response]);
        }

        return $payment->fresh();
    }

    public function verifyCallback(array $payload, string $signature): bool
    {
        ksort($payload);
        $values = '';
        foreach ($payload as $value) {
            $values .= is_array($value) ? json_encode($value, JSON_UNESCAPED_SLASHES) : (string) $value;
        }

        return $signature !== '' && hash_equals($this->sign([$values]), $signature);
    }

    public function cancel(PaywayPayment $payment): bool
    {
        $payment = $this->check($payment);
        if ($payment->status === 'paid') {
            return false;
        }

        $reqTime = now('UTC')->format('YmdHis');
        $merchantId = (string) config('services.payway.merchant_id');
        $payload = [
            'req_time' => $reqTime,
            'merchant_id' => $merchantId,
            'tran_id' => $payment->reference,
            'hash' => $this->sign([$reqTime, $merchantId, $payment->reference]),
        ];
        $response = Http::acceptJson()->asJson()->timeout(10)
            ->post($this->url('/api/payment-gateway/v1/payments/close-transaction'), $payload)
            ->throw()->json();
        if (! in_array((string) data_get($response, 'status.code', ''), ['0', '00'], true)) {
            throw new RuntimeException((string) data_get($response, 'status.message', 'PayWay could not close the transaction.'));
        }

        $payment->update(['status' => 'cancelled', 'provider_response' => $response]);

        return true;
    }

    public function simulatePaid(PaywayPayment $payment): PaywayPayment
    {
        if (! $this->canSimulate()) {
            throw new RuntimeException('PayWay sandbox simulation is disabled.');
        }

        return $this->markPaid($payment, [
            'original_currency' => $payment->currency,
            'original_amount' => (float) $payment->amount,
            'payment_status_code' => 0,
            'payment_status' => 'APPROVED',
            'apv' => 'SIMULATED',
        ], ['simulated' => true, 'environment' => 'local']);
    }

    public function canSimulate(): bool
    {
        return app()->environment('local')
            && str_contains((string) config('services.payway.base_url'), 'sandbox')
            && (bool) config('services.payway.allow_sandbox_simulation');
    }

    private function markPaid(PaywayPayment $payment, array $transaction, array $providerResponse): PaywayPayment
    {
        $currency = strtoupper((string) ($transaction['original_currency'] ?? $transaction['payment_currency'] ?? ''));
        $amount = (float) ($transaction['original_amount'] ?? $transaction['total_amount'] ?? -1);
        if ($currency !== $payment->currency || abs($amount - (float) $payment->amount) > 0.001) {
            throw new RuntimeException('PayWay transaction currency or amount does not match this order.');
        }

        return DB::transaction(function () use ($payment, $transaction, $providerResponse) {
            $locked = PaywayPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'paid') {
                return $locked;
            }
            $locked->update([
                'status' => 'paid',
                'approval_code' => $transaction['apv'] ?? null,
                'paid_at' => now(),
                'provider_response' => $providerResponse,
            ]);
            $locked->order()->update(['payment_status' => 'paid', 'status' => 'completed']);

            return $locked->fresh();
        });
    }

    private function sign(array $values): string
    {
        return base64_encode(hash_hmac('sha512', implode('', $values), (string) config('services.payway.api_key'), true));
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.payway.base_url'), '/').$path;
    }
}
