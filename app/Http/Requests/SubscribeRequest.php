<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'payment_provider' => ['required', 'string', 'in:stripe,apple,google,manual'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'store_transaction_id' => ['nullable', 'string', 'max:255'],
            'receipt' => ['nullable', 'string'],
        ];
    }
}
