@extends('admin.layouts.app')

@section('title', 'Add Drama')
@section('header', 'Add New Drama')

@section('content')
<div class="mx-auto max-w-4xl">
    <form method="POST" action="{{ route('admin.dramas.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6 space-y-6">
                <h3 class="text-base font-semibold text-gray-900 border-b pb-3">Basic Information</h3>

                {{-- Title --}}
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                </div>

                {{-- Synopsis --}}
                <div>
                    <label for="synopsis" class="block text-sm font-medium text-gray-700">Synopsis</label>
                    <textarea name="synopsis" id="synopsis" rows="2" maxlength="1000"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">{{ old('synopsis') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">Short summary shown in listings (max 1000 chars)</p>
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Full Description</label>
                    <textarea name="description" id="description" rows="4"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">{{ old('description') }}</textarea>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    {{-- Category --}}
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                        <select name="category_id" id="category_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                            <option value="">Select category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                        <select name="status" id="status" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                            @foreach(['draft','published','completed','suspended'] as $s)
                                <option value="{{ $s }}" {{ old('status', 'draft') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Content Rating --}}
                    <div>
                        <label for="content_rating" class="block text-sm font-medium text-gray-700">Content Rating</label>
                        <select name="content_rating" id="content_rating"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                            <option value="">None</option>
                            @foreach(['G','PG','PG-13','R','NC-17'] as $r)
                                <option value="{{ $r }}" {{ old('content_rating') === $r ? 'selected' : '' }}>{{ $r }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Language --}}
                    <div>
                        <label for="language" class="block text-sm font-medium text-gray-700">Language</label>
                        <input type="text" name="language" id="language" value="{{ old('language', 'Swahili') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>

                    {{-- Country --}}
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
                        <input type="text" name="country" id="country" value="{{ old('country', 'Tanzania') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>

                    {{-- Release Year --}}
                    <div>
                        <label for="release_year" class="block text-sm font-medium text-gray-700">Release Year</label>
                        <input type="number" name="release_year" id="release_year" value="{{ old('release_year', date('Y')) }}" min="1900" max="2100"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>
                </div>

                {{-- Director --}}
                <div>
                    <label for="director" class="block text-sm font-medium text-gray-700">Director</label>
                    <input type="text" name="director" id="director" value="{{ old('director') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                </div>

                {{-- Cast --}}
                <div>
                    <label for="cast" class="block text-sm font-medium text-gray-700">Cast</label>
                    <input type="text" name="cast" id="cast" value="{{ old('cast') }}" placeholder="Actor 1, Actor 2, Actor 3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Comma-separated list of actors</p>
                </div>

                {{-- Trailer URL --}}
                <div>
                    <label for="trailer_url" class="block text-sm font-medium text-gray-700">Trailer URL</label>
                    <input type="url" name="trailer_url" id="trailer_url" value="{{ old('trailer_url') }}" placeholder="https://youtube.com/watch?v=..."
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                </div>

                {{-- Tags --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                    <div class="flex flex-wrap gap-3">
                        @foreach($tags as $tag)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="tags[]" value="{{ $tag->id }}" {{ in_array($tag->id, old('tags', [])) ? 'checked' : '' }}
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
                        <input type="number" name="coin_price" id="coin_price" value="{{ old('coin_price', 0) }}" min="0"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">0 = free or use episode pricing</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-6">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_free" value="1" {{ old('is_free') ? 'checked' : '' }}
                            class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        <span class="ml-2 text-sm text-gray-700">Free Drama</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
                            class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        <span class="ml-2 text-sm text-gray-700">Featured</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_trending" value="1" {{ old('is_trending') ? 'checked' : '' }}
                            class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        <span class="ml-2 text-sm text-gray-700">Trending</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_new_release" value="1" {{ old('is_new_release') ? 'checked' : '' }}
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
                    <div x-data="{ preview: null }">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cover Image</label>
                        <div class="flex items-center gap-4">
                            <template x-if="preview">
                                <img :src="preview" class="h-24 w-18 rounded object-cover shadow">
                            </template>
                            <label class="cursor-pointer rounded-md border border-dashed border-gray-300 px-4 py-3 text-center hover:border-brand-400 flex-1">
                                <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span class="mt-1 block text-xs text-gray-500">Click to upload (max 5MB)</span>
                                <input type="file" name="cover_image" accept="image/*" class="hidden" @change="preview = URL.createObjectURL($event.target.files[0])">
                            </label>
                        </div>
                    </div>

                    <div x-data="{ preview: null }">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Banner Image</label>
                        <div class="flex items-center gap-4">
                            <template x-if="preview">
                                <img :src="preview" class="h-24 w-40 rounded object-cover shadow">
                            </template>
                            <label class="cursor-pointer rounded-md border border-dashed border-gray-300 px-4 py-3 text-center hover:border-brand-400 flex-1">
                                <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span class="mt-1 block text-xs text-gray-500">Click to upload (max 10MB)</span>
                                <input type="file" name="banner_image" accept="image/*" class="hidden" @change="preview = URL.createObjectURL($event.target.files[0])">
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('admin.dramas.index') }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">Cancel</a>
            <button type="submit" class="rounded-md bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                Create Drama
            </button>
        </div>
    </form>
</div>
@endsection
