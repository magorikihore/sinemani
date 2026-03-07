<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reportable_type' => ['required', 'string', 'in:comment,drama,episode'],
            'reportable_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'in:spam,inappropriate,copyright,harassment,other'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
