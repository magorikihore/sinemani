@extends('admin.layouts.app')
@section('title', 'App Settings')
@section('header', 'App Settings')

@section('content')
<div class="max-w-4xl">
    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
        @csrf @method('PUT')

        @foreach($settings as $group => $items)
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">{{ $group }}</h3>
            </div>
            <div class="px-6 py-4 space-y-4">
                @foreach($items as $setting)
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-start">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ str_replace('_', ' ', ucwords(str_replace('_', ' ', $setting->key))) }}</label>
                        <p class="text-xs text-gray-400 font-mono">{{ $setting->key }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        @if(in_array($setting->key, ['maintenance_mode']))
                            <select name="settings[{{ $setting->key }}]" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
                                <option value="false" {{ $setting->value === 'false' ? 'selected' : '' }}>Off</option>
                                <option value="true" {{ $setting->value === 'true' ? 'selected' : '' }}>On</option>
                            </select>
                        @elseif(strlen($setting->value ?? '') > 100)
                            <textarea name="settings[{{ $setting->key }}]" rows="3" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">{{ $setting->value }}</textarea>
                        @else
                            <input type="text" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach

        <div class="flex justify-end">
            <button type="submit" class="bg-brand-600 text-white px-6 py-2 rounded-md text-sm font-medium hover:bg-brand-700">
                Save All Settings
            </button>
        </div>
    </form>

    {{-- Add New Setting --}}
    <div class="mt-8 bg-white shadow rounded-lg p-6" x-data="{ open: false }">
        <button @click="open = !open" class="flex items-center text-sm font-medium text-brand-600 hover:text-brand-800">
            <svg class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add New Setting
        </button>
        <form x-show="open" x-cloak method="POST" action="{{ route('admin.settings.store') }}" class="mt-4 grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Key</label>
                <input type="text" name="key" required class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border" placeholder="setting_key">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Value</label>
                <input type="text" name="value" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border" placeholder="value">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Group</label>
                <input type="text" name="group" value="general" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
            </div>
            <div>
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-700 w-full">Add</button>
            </div>
        </form>
    </div>
</div>
@endsection
