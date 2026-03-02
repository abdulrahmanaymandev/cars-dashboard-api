<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'stockNo' => $this->stock_no,
            'make'    => $this->make,
            'model'   => $this->model,
            'year'    => (int) $this->year,
            'trim'    => $this->trim,
            'color'   => $this->color,
            'price'   => (float) $this->price,
            'stock'   => (int) $this->stock,
            'image'   => $this->image_url,
            'status'  => $this->status,
            'available' => $this->status === 'available',
        ];
    }
}
