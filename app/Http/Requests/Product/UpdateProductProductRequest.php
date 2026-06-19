<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'sometimes|required|integer',
            'name' => 'sometimes|required|string|max:255',
            'sku' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric',
            'is_active' => 'sometimes|nullable|boolean',
        ];
    }
}