@extends('admin.layouts.app')
@section('title', 'Subscriptions')
@section('header', 'Subscriptions')

@section('content')
<div class="space-y-6">
    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white shadow rounded-lg p-5">
            <dt class="text-sm text-gray-500">Active Subscriptions</dt>
            <dd class="text-2xl font-bold text-green-600 mt-1">{{ number_format($activeCount) }}</dd>
        </div>
        <div class="bg-white shadow rounded-lg p-5">
            <dt class="text-sm text-gray-500">Total Revenue</dt>
            <dd class="text-2xl font-bold text-brand-600 mt-1">TZS {{ number_format($totalRevenue) }}</dd>
        </div>
        <div class="bg-white shadow rounded-lg p-5 flex items-center justify-between">
            <div>
                <dt class="text-sm text-gray-500">Manage Plans</dt>
                <dd class="text-sm text-gray-700 mt-1">{{ $plans->count() }} plans</dd>
            </div>
            <a href="{{ route('admin.subscriptions.plans') }}" class="bg-brand-600 text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-brand-700">View Plans</a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white shadow rounded-lg p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Search User</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or email..." class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="status" class="rounded-md border-gray-300 text-sm px-3 py-2 border">
                    <option value="">All</option>
                    @foreach(['active','cancelled','expired','refunded'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Plan</label>
                <select name="plan_id" class="rounded-md border-gray-300 text-sm px-3 py-2 border">
                    <option value="">All Plans</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-brand-700">Filter</button>
                <a href="{{ route('admin.subscriptions.index') }}" class="ml-2 text-sm text-gray-500 hover:text-gray-700">Clear</a>
            </div>
        </form>
    </div>

    {{-- Subscriptions Table --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Starts</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ends</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($subscriptions as $sub)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm">
                            @if($sub->user)
                                <a href="{{ route('admin.users.show', $sub->user_id) }}" class="text-brand-600 hover:underline">{{ $sub->user->name }}</a>
                                <p class="text-xs text-gray-400">{{ $sub->user->email }}</p>
                            @else
                                <span class="text-gray-400">Deleted</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-medium">{{ $sub->plan?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm">TZS {{ number_format($sub->amount_paid) }}</td>
                        <td class="px-4 py-3">
                            @php
                                $colors = ['active' => 'green', 'cancelled' => 'yellow', 'expired' => 'gray', 'refunded' => 'red'];
                                $c = $colors[$sub->status] ?? 'gray';
                            @endphp
                            <span class="inline-flex rounded-full bg-{{ $c }}-100 px-2 py-1 text-xs font-medium text-{{ $c }}-800">{{ ucfirst($sub->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $sub->starts_at?->format('M d, Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $sub->ends_at?->format('M d, Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($sub->isActive())
                            <form method="POST" action="{{ route('admin.subscriptions.cancel', $sub) }}" class="inline" onsubmit="return confirm('Cancel this subscription?')">
                                @csrf @method('PATCH')
                                <input type="hidden" name="reason" value="Cancelled by admin">
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Cancel</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-500">No subscriptions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $subscriptions->links() }}</div>
</div>
@endsection
