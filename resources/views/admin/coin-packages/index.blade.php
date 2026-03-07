@extends('admin.layouts.app')
@section('title', 'Coin Packages')
@section('header', 'Coin Packages')

@section('content')
<div class="space-y-6">
    {{-- Add New Package --}}
    <div class="bg-white shadow rounded-lg" x-data="{ open: false }">
        <div class="px-6 py-4 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">All Packages</h3>
            <button @click="open = !open" class="bg-brand-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-brand-700">
                <span x-text="open ? 'Cancel' : '+ Add Package'"></span>
            </button>
        </div>

        {{-- Create Form --}}
        <form x-show="open" x-cloak method="POST" action="{{ route('admin.coin-packages.store') }}" class="px-6 pb-6 border-t border-gray-200 pt-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Name *</label>
                    <input type="text" name="name" required class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border" placeholder="Starter Pack">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Coins *</label>
                    <input type="number" name="coins" required min="1" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Bonus Coins</label>
                    <input type="number" name="bonus_coins" value="0" min="0" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Price (TZS) *</label>
                    <input type="number" step="0.01" name="price" required min="0.01" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Description</label>
                    <input type="text" name="description" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border" placeholder="Optional">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="1" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
                </div>
                <div class="flex items-end gap-4 pb-1">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_popular" value="1" class="rounded border-gray-300 text-brand-600 mr-1">
                        <span class="text-sm text-gray-700">Popular</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-brand-600 mr-1">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-700 w-full">Create Package</button>
                </div>
            </div>
        </form>
    </div>

    {{-- Packages Table --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Package</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Coins</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bonus</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200" x-data="{ editing: null }">
                @forelse($packages as $pkg)
                <tr class="hover:bg-gray-50">
                    {{-- View Mode --}}
                    <template x-if="editing !== {{ $pkg->id }}">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $pkg->name }}</div>
                            @if($pkg->description) <div class="text-xs text-gray-400">{{ $pkg->description }}</div> @endif
                            @if($pkg->is_popular) <span class="inline-flex rounded-full bg-yellow-100 px-2 py-0.5 text-xs text-yellow-800 mt-1">Popular</span> @endif
                        </td>
                    </template>
                    <template x-if="editing !== {{ $pkg->id }}">
                        <td class="px-6 py-4 text-sm text-gray-900">{{ number_format($pkg->coins) }}</td>
                    </template>
                    <template x-if="editing !== {{ $pkg->id }}">
                        <td class="px-6 py-4 text-sm text-green-600">+{{ number_format($pkg->bonus_coins) }}</td>
                    </template>
                    <template x-if="editing !== {{ $pkg->id }}">
                        <td class="px-6 py-4 text-sm font-bold text-brand-600">{{ number_format($pkg->total_coins) }}</td>
                    </template>
                    <template x-if="editing !== {{ $pkg->id }}">
                        <td class="px-6 py-4 text-sm font-medium">TZS {{ number_format($pkg->price, 2) }}</td>
                    </template>
                    <template x-if="editing !== {{ $pkg->id }}">
                        <td class="px-6 py-4">
                            @if($pkg->is_active)
                                <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">Active</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">Inactive</span>
                            @endif
                        </td>
                    </template>
                    <template x-if="editing !== {{ $pkg->id }}">
                        <td class="px-6 py-4 text-right space-x-2">
                            <button @click="editing = {{ $pkg->id }}" class="text-brand-600 hover:text-brand-900 text-sm font-medium">Edit</button>
                            <form method="POST" action="{{ route('admin.coin-packages.destroy', $pkg) }}" class="inline" onsubmit="return confirm('Delete this package?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                            </form>
                        </td>
                    </template>

                    {{-- Edit Mode --}}
                    <template x-if="editing === {{ $pkg->id }}">
                        <td colspan="7" class="px-6 py-4">
                            <form method="POST" action="{{ route('admin.coin-packages.update', $pkg) }}" class="flex flex-wrap gap-3 items-end">
                                @csrf @method('PUT')
                                <input type="text" name="name" value="{{ $pkg->name }}" class="rounded-md border-gray-300 text-sm px-2 py-1.5 border w-32" required>
                                <input type="number" name="coins" value="{{ $pkg->coins }}" class="rounded-md border-gray-300 text-sm px-2 py-1.5 border w-24" required min="1">
                                <input type="number" name="bonus_coins" value="{{ $pkg->bonus_coins }}" class="rounded-md border-gray-300 text-sm px-2 py-1.5 border w-24" min="0">
                                <input type="number" step="0.01" name="price" value="{{ $pkg->price }}" class="rounded-md border-gray-300 text-sm px-2 py-1.5 border w-28" required>
                                <input type="number" name="sort_order" value="{{ $pkg->sort_order }}" class="rounded-md border-gray-300 text-sm px-2 py-1.5 border w-16">
                                <label class="flex items-center"><input type="checkbox" name="is_popular" value="1" {{ $pkg->is_popular ? 'checked' : '' }} class="rounded border-gray-300 text-brand-600 mr-1"><span class="text-xs">Pop</span></label>
                                <label class="flex items-center"><input type="checkbox" name="is_active" value="1" {{ $pkg->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-brand-600 mr-1"><span class="text-xs">Active</span></label>
                                <button type="submit" class="bg-brand-600 text-white px-3 py-1.5 rounded text-sm">Save</button>
                                <button type="button" @click="editing = null" class="text-gray-500 hover:text-gray-700 text-sm">Cancel</button>
                            </form>
                        </td>
                    </template>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">No coin packages yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
