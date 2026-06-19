<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|nullable|string|max:255',
            'code' => 'sometimes|nullable|string|max:255',
            'price' => 'sometimes|nullable|numeric',
            'billing_cycle' => 'sometimes|nullable|string|max:255',
            'is_active' => 'sometimes|nullable|string',
        ];
    }
}