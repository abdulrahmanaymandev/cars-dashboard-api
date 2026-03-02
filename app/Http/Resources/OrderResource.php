<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->loadMissing('items.car');

        return [
            // 'id' is the display-friendly order_no (e.g. ORD-001).
            // It is also the route key — used for PATCH/DELETE API calls.
            'id'           => $this->order_no,
            'customerName' => $this->customer_name,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'date'         => $this->order_date?->format('Y-m-d'),
            'status'       => $this->status,
            'total'        => (float) $this->total,
            'items'        => $this->items->map(function ($item) {
                return [
                    'stockNo'   => $item->car?->stock_no,
                    'carId'     => $item->car_id,  // numeric — used to look up car image in the View modal
                    'qty'       => (int)   $item->qty,
                    'unitPrice' => (float) $item->unit_price,
                    'price'     => (float) $item->unit_price, // alias for calcItemTotal compatibility
                ];
            })->values(),
        ];
    }
}
