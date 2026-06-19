<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'sometimes|required|integer',
            'product_id' => 'sometimes|required|integer',
            'quantity' => 'sometimes|required|integer',
            'order_date' => 'sometimes|required|date',
            'status' => 'sometimes|nullable|string',
        ];
    }
}