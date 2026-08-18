<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaywayPayment;
use App\Services\PaywayPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PaywayPaymentController extends Controller
{
    public function show(Order $order, PaywayPaymentService $service): JsonResponse
    {
        abort_unless($order->payment_method === 'qr' && $order->paywayPayment, 404);

        return response()->json($this->response($service->check($order->paywayPayment)));
    }

    public function callback(Request $request, PaywayPaymentService $service): JsonResponse
    {
        $payload = $request->json()->all();
        $signature = (string) $request->header('X-PayWay-HMAC-SHA512');
        if (! $service->verifyCallback($payload, $signature)) {
            throw new AccessDeniedHttpException('Invalid PayWay callback signature.');
        }

        $data = validator($payload, [
            'tran_id' => ['required', 'string', 'max:20'],
            'status' => ['required'],
        ])->validate();
        $payment = PaywayPayment::where('reference', $data['tran_id'])->firstOrFail();
        if (in_array((string) $data['status'], ['0', '00'], true)) {
            $service->check($payment);
        }

        return response()->json(['received' => true]);
    }

    public function simulate(Order $order, PaywayPaymentService $service): JsonResponse
    {
        abort_unless($service->canSimulate(), 403, 'PayWay sandbox simulation is disabled.');
        abort_unless($order->payment_method === 'qr' && $order->paywayPayment, 404);

        return response()->json($this->response($service->simulatePaid($order->paywayPayment)));
    }

    private function response(PaywayPayment $payment): array
    {
        return [
            'reference' => $payment->reference,
            'qr' => $payment->qr_payload,
            'qr_image' => $payment->qr_image,
            'deeplink' => $payment->deeplink,
            'currency' => $payment->currency,
            'amount' => $payment->amount,
            'status' => $payment->status,
            'expires_at' => $payment->expires_at,
            'paid_at' => $payment->paid_at,
            'environment' => str_contains((string) config('services.payway.base_url'), 'sandbox') ? 'sandbox' : 'production',
            'can_simulate' => app()->environment('local') && (bool) config('services.payway.allow_sandbox_simulation'),
        ];
    }
}
