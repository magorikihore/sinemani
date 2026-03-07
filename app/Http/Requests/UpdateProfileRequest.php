<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'username' => ['sometimes', 'string', 'max:50', 'alpha_dash', "unique:users,username,{$userId}"],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20', "unique:users,phone,{$userId}"],
            'avatar' => ['sometimes', 'image', 'max:2048'], // 2MB max
            'gender' => ['sometimes', 'in:male,female,other'],
            'date_of_birth' => ['sometimes', 'date', 'before:today'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:500'],
            'language' => ['sometimes', 'string', 'max:5'],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }
}
