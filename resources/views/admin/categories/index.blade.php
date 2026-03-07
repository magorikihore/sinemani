@extends('admin.layouts.app')

@section('title', 'Categories')
@section('header', 'Categories')

@section('content')
<div class="space-y-6">
    {{-- Add Category Form --}}
    <div class="bg-white shadow rounded-lg" x-data="{ open: false }">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900">Categories ({{ $categories->count() }})</h3>
            <button @click="open = !open" class="inline-flex items-center rounded-md bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-brand-500">
                <svg class="-ml-0.5 mr-1 h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/></svg>
                Add Category
            </button>
        </div>

        <div x-show="open" x-cloak class="border-b border-gray-200 bg-gray-50 px-4 py-4 sm:px-6">
            <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-4 sm:grid-cols-4 items-end">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <input type="text" name="description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Sort Order</label>
                    <input type="number" name="sort_order" value="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div class="flex items-center gap-3">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-brand-600">
                        <span class="ml-2 text-sm">Active</span>
                    </label>
                    <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">Save</button>
                </div>
            </form>
        </div>

        {{-- Categories List --}}
        <div class="divide-y divide-gray-200">
            @forelse($categories as $cat)
            <div class="px-4 py-4 sm:px-6" x-data="{ editing: false }">
                {{-- View Mode --}}
                <div x-show="!editing" class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        @if($cat->icon)
                            <img src="{{ Storage::url($cat->icon) }}" class="h-8 w-8 rounded object-cover">
                        @else
                            <div class="h-8 w-8 rounded bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-500">{{ strtoupper(substr($cat->name, 0, 1)) }}</div>
                        @endif
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $cat->name }}</p>
                            <p class="text-xs text-gray-500">{{ $cat->dramas_count }} dramas &bull; Order: {{ $cat->sort_order }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $cat->is_active ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                            {{ $cat->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <button @click="editing = true" class="text-sm text-brand-600 hover:text-brand-800">Edit</button>
                        @if($cat->dramas_count === 0)
                        <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}" x-data
                            @submit.prevent="if(confirm('Delete category?')) $el.submit()">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">Delete</button>
                        </form>
                        @endif
                    </div>
                </div>

                {{-- Edit Mode --}}
                <div x-show="editing" x-cloak>
                    <form method="POST" action="{{ route('admin.categories.update', $cat) }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-4 sm:grid-cols-4 items-end">
                        @csrf @method('PUT')
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Name</label>
                            <input type="text" name="name" value="{{ $cat->name }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Description</label>
                            <input type="text" name="description" value="{{ $cat->description }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Sort Order</label>
                            <input type="number" name="sort_order" value="{{ $cat->sort_order }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_active" value="1" {{ $cat->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-brand-600">
                                <span class="ml-2 text-xs">Active</span>
                            </label>
                            <button type="submit" class="rounded-md bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-brand-500">Save</button>
                            <button type="button" @click="editing = false" class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-sm text-gray-500">No categories yet. Click "Add Category" to create one.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
