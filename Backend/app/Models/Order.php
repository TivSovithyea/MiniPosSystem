<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['order_number', 'customer_id', 'subtotal', 'tax', 'discount', 'total', 'payment_method', 'payment_status', 'status', 'notes', 'ordered_at'])]
class Order extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['subtotal' => 'decimal:2', 'tax' => 'decimal:2', 'discount' => 'decimal:2', 'total' => 'decimal:2', 'ordered_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paywayPayment(): HasOne
    {
        return $this->hasOne(PaywayPayment::class);
    }
}
