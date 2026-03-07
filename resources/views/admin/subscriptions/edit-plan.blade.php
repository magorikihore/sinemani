@extends('admin.layouts.app')
@section('title', 'Edit Plan')
@section('header', 'Edit Subscription Plan')

@section('content')
<div class="max-w-3xl">
    <a href="{{ route('admin.subscriptions.plans') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-6">
        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        Back to Plans
    </a>

    <form method="POST" action="{{ route('admin.subscriptions.plans.update', $plan) }}" class="bg-white shadow rounded-lg p-6 space-y-5">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Plan Name *</label>
                <input type="text" name="name" value="{{ old('name', $plan->name) }}" required class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Interval *</label>
                <select name="interval" required class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
                    @foreach(['weekly','monthly','yearly'] as $int)
                        <option value="{{ $int }}" {{ old('interval', $plan->interval) === $int ? 'selected' : '' }}>{{ ucfirst($int) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="2" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">{{ old('description', $plan->description) }}</textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Duration (days) *</label>
                <input type="number" name="duration_days" value="{{ old('duration_days', $plan->duration_days) }}" min="1" required class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Price (TZS) *</label>
                <input type="number" step="0.01" name="price" value="{{ old('price', $plan->price) }}" min="0" required class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Original Price</label>
                <input type="number" step="0.01" name="original_price" value="{{ old('original_price', $plan->original_price) }}" min="0" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                <input type="text" name="currency" value="{{ old('currency', $plan->currency ?? 'TZS') }}" maxlength="3" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Coin Bonus</label>
                <input type="number" name="coin_bonus" value="{{ old('coin_bonus', $plan->coin_bonus) }}" min="0" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Daily Coin Bonus</label>
                <input type="number" name="daily_coin_bonus" value="{{ old('daily_coin_bonus', $plan->daily_coin_bonus) }}" min="0" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Features (one per line)</label>
            <textarea name="features" rows="4" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">{{ old('features', is_array($plan->features) ? implode("\n", $plan->features) : $plan->features) }}</textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $plan->sort_order) }}" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
            </div>
            <div class="flex items-center pt-6">
                <label class="flex items-center">
                    <input type="checkbox" name="is_popular" value="1" {{ old('is_popular', $plan->is_popular) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-600 mr-2">
                    <span class="text-sm text-gray-700">Mark as Popular</span>
                </label>
            </div>
            <div class="flex items-center pt-6">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-600 mr-2">
                    <span class="text-sm text-gray-700">Active</span>
                </label>
            </div>
        </div>

        <div class="pt-4 border-t flex justify-between">
            <a href="{{ route('admin.subscriptions.plans') }}" class="text-gray-600 hover:text-gray-800 text-sm">Cancel</a>
            <button type="submit" class="bg-brand-600 text-white px-6 py-2 rounded-md text-sm font-medium hover:bg-brand-700">Update Plan</button>
        </div>
    </form>
</div>
@endsection
