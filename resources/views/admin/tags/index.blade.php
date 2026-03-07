@extends('admin.layouts.app')

@section('title', 'Tags')
@section('header', 'Tags')

@section('content')
<div class="space-y-6">
    <div class="bg-white shadow rounded-lg" x-data="{ open: false }">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900">Tags ({{ $tags->count() }})</h3>
            <button @click="open = !open" class="inline-flex items-center rounded-md bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-brand-500">
                <svg class="-ml-0.5 mr-1 h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/></svg>
                Add Tag
            </button>
        </div>

        {{-- Add Tag Form --}}
        <div x-show="open" x-cloak class="border-b border-gray-200 bg-gray-50 px-4 py-4 sm:px-6">
            <form method="POST" action="{{ route('admin.tags.store') }}" class="flex items-end gap-4">
                @csrf
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700">Tag Name</label>
                    <input type="text" name="name" required placeholder="e.g. Romance, Comedy, Thriller"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">Add Tag</button>
            </form>
        </div>

        {{-- Tags List --}}
        <div class="p-4 sm:p-6">
            @if($tags->count())
            <div class="flex flex-wrap gap-3">
                @foreach($tags as $tag)
                <div class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 bg-white" x-data="{ editing: false }">
                    {{-- View --}}
                    <div x-show="!editing" class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-700">{{ $tag->name }}</span>
                        <span class="text-xs text-gray-400">({{ $tag->dramas_count }})</span>
                        <span class="h-2 w-2 rounded-full {{ $tag->is_active ? 'bg-green-500' : 'bg-red-500' }}"></span>
                        <button @click="editing = true" class="ml-1 text-gray-400 hover:text-brand-600" title="Edit">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </button>
                    </div>

                    {{-- Edit --}}
                    <div x-show="editing" x-cloak class="flex items-center gap-2">
                        <form method="POST" action="{{ route('admin.tags.update', $tag) }}" class="flex items-center gap-2">
                            @csrf @method('PUT')
                            <input type="text" name="name" value="{{ $tag->name }}" required class="w-32 rounded-md border-gray-300 shadow-sm text-sm py-1">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_active" value="1" {{ $tag->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-brand-600 h-3.5 w-3.5">
                            </label>
                            <button type="submit" class="text-green-600 hover:text-green-800">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.tags.destroy', $tag) }}" x-data
                            @submit.prevent="if(confirm('Delete tag \'{{ $tag->name }}\'?')) $el.submit()">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                        <button @click="editing = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-center text-sm text-gray-500 py-8">No tags yet. Click "Add Tag" to create one.</p>
            @endif
        </div>
    </div>
</div>
@endsection
