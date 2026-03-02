<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_no',
        'customer_name',
        'email',
        'phone',
        'order_date',
        'status',
        'total'
    ];

    protected $casts = [
        'order_date' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Use order_no (e.g. ORD-001) for route model binding
     * so the frontend can identify orders by their display ID.
     */
    public function getRouteKeyName(): string
    {
        return 'order_no';
    }
}
