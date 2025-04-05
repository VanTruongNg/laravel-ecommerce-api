<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

enum FuelType: string
{
    case GASOLINE = 'gasoline';
    case DIESEL = 'diesel';
    case ELECTRIC = 'electric';
    case HYBRID = 'hybrid';
}

enum Availability: string
{
    case IN_STOCK = 'in_stock';
    case PRE_ORDER = 'pre_order';
    case OUT_OF_STOCK = 'out_of_stock';
}

class Car extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'model',
        'year',
        'color',
        'price',
        'brand_id',
        'image_url',
        'stock',
        'fuel_type',
        'availability',
    ];

    protected function casts(): array
    {
        return [
            'fuel_type' => FuelType::class,
            'availability' => Availability::class,
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function carts(): BelongsToMany
    {
        return $this->belongsToMany(Cart::class, 'cart_car', 'car_id', 'cart_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}