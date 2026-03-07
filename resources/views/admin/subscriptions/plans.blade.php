@extends('admin.layouts.app')
@section('title', 'Subscription Plans')
@section('header', 'Subscription Plans')

@section('content')
<div class="space-y-6">
    <div class="flex justify-end">
        <a href="{{ route('admin.subscriptions.plans.create') }}" class="bg-brand-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-brand-700">+ New Plan</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($plans as $plan)
        <div class="bg-white shadow rounded-lg overflow-hidden {{ !$plan->is_active ? 'opacity-60' : '' }}">
            <div class="p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-bold text-gray-900">{{ $plan->name }}</h3>
                    @if($plan->is_popular)
                        <span class="inline-flex rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">Popular</span>
                    @endif
                </div>
                <div class="mb-4">
                    <span class="text-3xl font-bold text-brand-600">TZS {{ number_format($plan->price) }}</span>
                    <span class="text-gray-500 text-sm">/{{ $plan->interval }}</span>
                    @if($plan->original_price && $plan->original_price > $plan->price)
                        <span class="text-sm text-gray-400 line-through ml-2">TZS {{ number_format($plan->original_price) }}</span>
                    @endif
                </div>
                <p class="text-sm text-gray-600 mb-3">{{ $plan->description ?? 'No description' }}</p>

                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Duration</dt><dd>{{ $plan->duration_days }} days</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Coin Bonus</dt><dd>{{ $plan->coin_bonus ?? 0 }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Daily Bonus</dt><dd>{{ $plan->daily_coin_bonus ?? 0 }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Total Subs</dt><dd>{{ $plan->subscriptions_count }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Active Subs</dt><dd class="font-medium text-green-600">{{ $plan->active_count }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Status</dt>
                        <dd>
                            @if($plan->is_active)
                                <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">Active</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">Inactive</span>
                            @endif
                        </dd>
                    </div>
                </dl>

                @if($plan->features && is_array($plan->features) && count($plan->features))
                <div class="mt-4 pt-4 border-t">
                    <p class="text-xs font-medium text-gray-500 mb-2">Features:</p>
                    <ul class="space-y-1">
                        @foreach($plan->features as $feature)
                        <li class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 text-green-500 mr-2 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                            {{ $feature }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            <div class="px-6 py-3 bg-gray-50 border-t flex justify-between">
                <a href="{{ route('admin.subscriptions.plans.edit', $plan) }}" class="text-brand-600 hover:text-brand-900 text-sm font-medium">Edit</a>
                @if($plan->active_count == 0)
                <form method="POST" action="{{ route('admin.subscriptions.plans.destroy', $plan) }}" onsubmit="return confirm('Delete this plan?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-3 text-center py-12 text-gray-500">No subscription plans yet.</div>
        @endforelse
    </div>
</div>
@endsection
