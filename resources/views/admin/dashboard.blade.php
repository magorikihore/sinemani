@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
<div class="space-y-6">
    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Users --}}
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Total Users</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($stats['users']['total']) }}</dd>
            <dd class="mt-1 text-xs text-gray-500">+{{ $stats['users']['today'] }} today &bull; {{ $stats['users']['vip'] }} VIP</dd>
        </div>

        {{-- Dramas --}}
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Dramas</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ $stats['content']['total_dramas'] }}</dd>
            <dd class="mt-1 text-xs text-gray-500">{{ $stats['content']['published_dramas'] }} published &bull; {{ $stats['content']['total_episodes'] }} episodes</dd>
        </div>

        {{-- Revenue --}}
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Revenue (Month)</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">TZS {{ number_format($stats['payments']['month_revenue']) }}</dd>
            <dd class="mt-1 text-xs text-gray-500">TZS {{ number_format($stats['payments']['today_revenue']) }} today &bull; {{ $stats['payments']['pending'] }} pending</dd>
        </div>

        {{-- Subscriptions --}}
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Active Subscriptions</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($stats['subscriptions']['active']) }}</dd>
            <dd class="mt-1 text-xs text-gray-500">+{{ $stats['subscriptions']['new_today'] }} today</dd>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Recent Dramas --}}
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">Recent Dramas</h3>
                <a href="{{ route('admin.dramas.index') }}" class="text-sm text-brand-600 hover:text-brand-800 font-medium">View all</a>
            </div>
            <ul role="list" class="divide-y divide-gray-200">
                @forelse($recentDramas as $drama)
                <li class="px-4 py-4 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.dramas.show', $drama) }}" class="text-sm font-medium text-brand-600 hover:text-brand-800 truncate">{{ $drama->title }}</a>
                            <p class="text-xs text-gray-500">{{ $drama->category?->name ?? 'No category' }} &bull; {{ $drama->total_episodes }} episodes</p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $drama->status === 'published' ? 'bg-green-50 text-green-700' : ($drama->status === 'draft' ? 'bg-yellow-50 text-yellow-700' : 'bg-gray-50 text-gray-700') }}">
                            {{ ucfirst($drama->status) }}
                        </span>
                    </div>
                </li>
                @empty
                <li class="px-4 py-8 text-center text-sm text-gray-500">No dramas yet</li>
                @endforelse
            </ul>
        </div>

        {{-- Recent Users --}}
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Recent Users</h3>
            </div>
            <ul role="list" class="divide-y divide-gray-200">
                @forelse($recentUsers as $user)
                <li class="px-4 py-4 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500">{{ $user->email }}</p>
                        </div>
                        <span class="text-xs text-gray-400">{{ $user->created_at->diffForHumans() }}</span>
                    </div>
                </li>
                @empty
                <li class="px-4 py-8 text-center text-sm text-gray-500">No users yet</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
