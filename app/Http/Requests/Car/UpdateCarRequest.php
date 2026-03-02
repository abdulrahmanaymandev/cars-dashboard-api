<?php

namespace App\Http\Requests\Car;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stockNo' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('cars', 'stock_no')->ignore($this->route('car'))
            ],
            'make'    => ['sometimes', 'string', 'max:100'],
            'model'   => ['sometimes', 'string', 'max:100'],
            'year'    => ['sometimes', 'integer', 'min:1900', 'max:2100'],
            'trim'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'color'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'price'   => ['sometimes', 'numeric', 'min:0'],
            'stock'   => ['sometimes', 'integer', 'min:0'],
            'image'   => ['sometimes', 'nullable', 'string', 'max:2048'],
        ];
    }
}
