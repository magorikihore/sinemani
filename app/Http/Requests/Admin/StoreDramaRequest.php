<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDramaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'synopsis' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'banner_image' => ['nullable', 'image', 'max:5120'],
            'trailer_url' => ['nullable', 'url'],
            'category_id' => ['required', 'exists:categories,id'],
            'status' => ['sometimes', 'in:draft,published,completed,suspended'],
            'content_rating' => ['sometimes', 'in:G,PG,PG-13,R,NC-17'],
            'language' => ['sometimes', 'string', 'max:5'],
            'country' => ['nullable', 'string', 'max:100'],
            'release_year' => ['nullable', 'integer', 'min:1900', 'max:2099'],
            'director' => ['nullable', 'string', 'max:255'],
            'cast' => ['nullable', 'array'],
            'cast.*' => ['string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_trending' => ['sometimes', 'boolean'],
            'is_new_release' => ['sometimes', 'boolean'],
            'is_free' => ['sometimes', 'boolean'],
            'coin_price' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
