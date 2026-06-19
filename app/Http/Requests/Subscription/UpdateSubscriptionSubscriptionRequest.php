<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscriber_id' => 'sometimes|nullable|integer',
            'plan_id' => 'sometimes|nullable|integer',
            'starts_at' => 'sometimes|nullable|date',
            'ends_at' => 'sometimes|nullable|date',
            'status' => 'sometimes|nullable|string|max:255',
            'name' => 'sometimes|required|string|max:255',
        ];
    }
}