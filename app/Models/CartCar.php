<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CartCar extends Pivot
{
    protected $fillable = [
        'cart_id',
        'car_id',
        'quantity',
    ];
}
