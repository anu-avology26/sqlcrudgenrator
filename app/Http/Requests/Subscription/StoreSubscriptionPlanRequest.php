<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'billing_cycle' => 'nullable|string|max:255',
            'is_active' => 'nullable|string',
        ];
    }
}