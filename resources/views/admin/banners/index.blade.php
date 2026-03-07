@extends('admin.layouts.app')
@section('title', 'Banners')
@section('header', 'Banners')

@section('content')
<div class="space-y-6">
    {{-- Add Banner --}}
    <div class="bg-white shadow rounded-lg" x-data="{ open: false, preview: null }">
        <div class="px-6 py-4 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">App Banners</h3>
            <button @click="open = !open" class="bg-brand-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-brand-700">
                <span x-text="open ? 'Cancel' : '+ Add Banner'"></span>
            </button>
        </div>
        <form x-show="open" x-cloak method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data" class="px-6 pb-6 border-t pt-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Title *</label>
                    <input type="text" name="title" required class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border" placeholder="Banner title">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Link Type</label>
                    <select name="link_type" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
                        <option value="">None</option>
                        <option value="drama">Drama</option>
                        <option value="url">External URL</option>
                        <option value="category">Category</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Link Value</label>
                    <input type="text" name="link_value" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border" placeholder="Drama ID, URL or Category ID">
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Image * (max 5MB)</label>
                    <input type="file" name="image" accept="image/*" required class="text-sm" @change="preview = URL.createObjectURL($event.target.files[0])">
                    <img x-show="preview" :src="preview" class="mt-2 h-20 rounded object-cover" x-cloak>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="1" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Start Date</label>
                    <input type="date" name="starts_at" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">End Date</label>
                    <input type="date" name="ends_at" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
                </div>
            </div>
            <div class="flex items-center gap-4">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-brand-600 mr-1">
                    <span class="text-sm text-gray-700">Active</span>
                </label>
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-700">Create Banner</button>
            </div>
        </form>
    </div>

    {{-- Banners Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($banners as $banner)
        <div class="bg-white shadow rounded-lg overflow-hidden {{ !$banner->is_active ? 'opacity-60' : '' }}">
            <div class="aspect-[16/7] bg-gray-100">
                <img src="{{ asset('storage/' . $banner->image) }}" alt="{{ $banner->title }}" class="w-full h-full object-cover">
            </div>
            <div class="p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-semibold text-gray-900 text-sm">{{ $banner->title }}</h4>
                    @if($banner->is_active)
                        <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">Active</span>
                    @else
                        <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">Inactive</span>
                    @endif
                </div>
                <dl class="text-xs text-gray-500 space-y-1">
                    @if($banner->link_type)
                        <div class="flex justify-between"><dt>Link</dt><dd>{{ $banner->link_type }}: {{ $banner->link_value }}</dd></div>
                    @endif
                    <div class="flex justify-between"><dt>Order</dt><dd>{{ $banner->sort_order }}</dd></div>
                    @if($banner->starts_at)
                        <div class="flex justify-between"><dt>Period</dt><dd>{{ $banner->starts_at->format('M d') }} — {{ $banner->ends_at?->format('M d') ?? 'forever' }}</dd></div>
                    @endif
                </dl>
                <div class="mt-3 flex justify-between items-center">
                    <form method="POST" action="{{ route('admin.banners.update', $banner) }}" enctype="multipart/form-data" class="inline">
                        @csrf @method('PUT')
                        <input type="hidden" name="title" value="{{ $banner->title }}">
                        <input type="hidden" name="is_active" value="{{ $banner->is_active ? '0' : '1' }}">
                        <button type="submit" class="text-sm {{ $banner->is_active ? 'text-yellow-600 hover:text-yellow-800' : 'text-green-600 hover:text-green-800' }} font-medium">
                            {{ $banner->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" onsubmit="return confirm('Delete this banner?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-3 text-center py-12 text-gray-500">No banners yet.</div>
        @endforelse
    </div>
</div>
@endsection
