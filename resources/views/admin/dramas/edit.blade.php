@extends('admin.layouts.app')

@section('title', 'Edit: ' . $drama->title)
@section('header', 'Edit Drama')

@section('content')
<div class="mx-auto max-w-4xl">
    <form method="POST" action="{{ route('admin.dramas.update', $drama) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6 space-y-6">
                <h3 class="text-base font-semibold text-gray-900 border-b pb-3">Basic Information</h3>

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" value="{{ old('title', $drama->title) }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                </div>

                <div>
                    <label for="synopsis" class="block text-sm font-medium text-gray-700">Synopsis</label>
                    <textarea name="synopsis" id="synopsis" rows="2" maxlength="1000"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">{{ old('synopsis', $drama->synopsis) }}</textarea>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Full Description</label>
                    <textarea name="description" id="description" rows="4"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">{{ old('description', $drama->description) }}</textarea>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                        <select name="category_id" id="category_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $drama->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                        <select name="status" id="status" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                            @foreach(['draft','published','completed','suspended'] as $s)
                                <option value="{{ $s }}" {{ old('status', $drama->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="content_rating" class="block text-sm font-medium text-gray-700">Content Rating</label>
                        <select name="content_rating" id="content_rating"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                            <option value="">None</option>
                            @foreach(['G','PG','PG-13','R','NC-17'] as $r)
                                <option value="{{ $r }}" {{ old('content_rating', $drama->content_rating) === $r ? 'selected' : '' }}>{{ $r }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="language" class="block text-sm font-medium text-gray-700">Language</label>
                        <input type="text" name="language" id="language" value="{{ old('language', $drama->language) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
                        <input type="text" name="country" id="country" value="{{ old('country', $drama->country) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="release_year" class="block text-sm font-medium text-gray-700">Release Year</label>
                        <input type="number" name="release_year" id="release_year" value="{{ old('release_year', $drama->release_year) }}" min="1900" max="2100"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>
                </div>

                <div>
                    <label for="director" class="block text-sm font-medium text-gray-700">Director</label>
                    <input type="text" name="director" id="director" value="{{ old('director', $drama->director) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                </div>

                <div>
                    <label for="cast" class="block text-sm font-medium text-gray-700">Cast</label>
                    <input type="text" name="cast" id="cast" value="{{ old('cast', is_array($drama->cast) ? implode(', ', $drama->cast) : $drama->cast) }}" placeholder="Actor 1, Actor 2"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                </div>

                <div>
                    <label for="trailer_url" class="block text-sm font-medium text-gray-700">Trailer URL</label>
                    <input type="url" name="trailer_url" id="trailer_url" value="{{ old('trailer_url', $drama->trailer_url) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                    <div class="flex flex-wrap gap-3">
                        @php $selectedTags = old('tags', $drama->tags->pluck('id')->toArray()); @endphp
                        @foreach($tags as $tag)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="tags[]" value="{{ $tag->id }}" {{ in_array($tag->id, $selectedTags) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <span class="ml-2 text-sm text-gray-700">{{ $tag->name }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Pricing --}}
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6 space-y-6">
                <h3 class="text-base font-semibold text-gray-900 border-b pb-3">Pricing & Visibility</h3>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div>
                        <label for="coin_price" class="block text-sm font-medium text-gray-700">Coin Price</label>
                        <input type="number" name="coin_price" id="coin_price" value="{{ old('coin_price', $drama->coin_price) }}" min="0"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>
                </div>
                <div class="flex flex-wrap gap-6">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_free" value="1" {{ old('is_free', $drama->is_free) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        <span class="ml-2 text-sm text-gray-700">Free Drama</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $drama->is_featured) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        <span class="ml-2 text-sm text-gray-700">Featured</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_trending" value="1" {{ old('is_trending', $drama->is_trending) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        <span class="ml-2 text-sm text-gray-700">Trending</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_new_release" value="1" {{ old('is_new_release', $drama->is_new_release) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        <span class="ml-2 text-sm text-gray-700">New Release</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Images --}}
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6 space-y-6">
                <h3 class="text-base font-semibold text-gray-900 border-b pb-3">Images</h3>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div x-data="{ preview: '{{ $drama->cover_image ? Storage::url($drama->cover_image) : '' }}' }">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cover Image</label>
                        <div class="flex items-center gap-4">
                            <template x-if="preview">
                                <img :src="preview" class="h-24 w-18 rounded object-cover shadow">
                            </template>
                            <label class="cursor-pointer rounded-md border border-dashed border-gray-300 px-4 py-3 text-center hover:border-brand-400 flex-1">
                                <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span class="mt-1 block text-xs text-gray-500">Click to change (max 5MB)</span>
                                <input type="file" name="cover_image" accept="image/*" class="hidden" @change="preview = URL.createObjectURL($event.target.files[0])">
                            </label>
                        </div>
                    </div>

                    <div x-data="{ preview: '{{ $drama->banner_image ? Storage::url($drama->banner_image) : '' }}' }">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Banner Image</label>
                        <div class="flex items-center gap-4">
                            <template x-if="preview">
                                <img :src="preview" class="h-24 w-40 rounded object-cover shadow">
                            </template>
                            <label class="cursor-pointer rounded-md border border-dashed border-gray-300 px-4 py-3 text-center hover:border-brand-400 flex-1">
                                <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span class="mt-1 block text-xs text-gray-500">Click to change (max 10MB)</span>
                                <input type="file" name="banner_image" accept="image/*" class="hidden" @change="preview = URL.createObjectURL($event.target.files[0])">
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between">
            <div>
                {{-- Delete button triggers the separate form outside via Alpine --}}
                <button type="button" class="text-sm font-semibold text-red-600 hover:text-red-800"
                    onclick="if(confirm('Are you sure you want to delete this drama? This action cannot be undone.')) document.getElementById('delete-drama-form').submit()">
                    Delete Drama
                </button>
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.dramas.show', $drama) }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">Cancel</a>
                <button type="submit" class="rounded-md bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-500">
                    Save Changes
                </button>
            </div>
        </div>
    </form>

    {{-- Separate delete form (outside the edit form to avoid nesting) --}}
    <form id="delete-drama-form" method="POST" action="{{ route('admin.dramas.destroy', $drama) }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection
