<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'max:50', 'unique:orders,order_no'], // ORD-001
            'customerName' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'date' => ['required', 'date'],
            'status' => ['required', 'in:pending,completed,cancelled'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.stockNo' => ['required', 'string', 'exists:cars,stock_no'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            // price اختياري (لو حبيت تاخذه من DB)، بس نخليه موجود عشان يطابق الفرونت
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
