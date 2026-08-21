<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['category_id', 'name', 'sku', 'description', 'price', 'cost', 'stock', 'image', 'is_active'])]
class Product extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'cost' => 'decimal:2', 'is_active' => 'boolean'];
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value && ! str_starts_with($value, 'http')
                ? asset('storage/'.$value)
                : $value,
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
