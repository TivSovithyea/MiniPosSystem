<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'reference', 'qr_payload', 'qr_image', 'deeplink', 'currency', 'amount', 'status', 'approval_code', 'expires_at', 'paid_at', 'provider_response'])]
class PaywayPayment extends Model
{
    protected $hidden = ['provider_response'];

    protected $appends = ['environment', 'can_simulate'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'provider_response' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    protected function environment(): Attribute
    {
        return Attribute::get(fn () => str_contains((string) config('services.payway.base_url'), 'sandbox') ? 'sandbox' : 'production');
    }

    protected function canSimulate(): Attribute
    {
        return Attribute::get(fn () => app()->environment('local')
            && str_contains((string) config('services.payway.base_url'), 'sandbox')
            && (bool) config('services.payway.allow_sandbox_simulation'));
    }
}
