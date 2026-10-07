<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = []; // every write to this model goes through OrderController, which computes the total itself

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'subtotal' => 'integer',
            'delivery_fee' => 'integer',
            'total' => 'integer',
        ];
    }

    const NEXT_STATUSES = [
        'processing', 'in_production', 'ready_for_delivery', 'delivered', 'cancelled',
    ];
}
