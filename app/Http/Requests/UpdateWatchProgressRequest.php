<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWatchProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'progress' => ['required', 'integer', 'min:0'],
            'duration' => ['required', 'integer', 'min:0'],
        ];
    }
}
