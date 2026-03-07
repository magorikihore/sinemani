<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreEpisodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
            'video' => ['nullable', 'file', 'mimes:mp4,mov,avi,mkv', 'max:512000'], // 500MB
            'video_url' => ['nullable', 'url'],
            'episode_number' => ['required', 'integer', 'min:1'],
            'season_number' => ['sometimes', 'integer', 'min:1'],
            'is_free' => ['sometimes', 'boolean'],
            'coin_price' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:draft,processing,published,failed'],
        ];
    }
}
