@extends('admin.layouts.app')
@section('title', 'Transactions')
@section('header', 'Mobile Payments')

@section('content')
<div class="space-y-6">
    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white shadow rounded-lg p-5">
            <dt class="text-sm text-gray-500">Today Revenue</dt>
            <dd class="text-2xl font-bold text-green-600 mt-1">TZS {{ number_format($stats['today_revenue']) }}</dd>
            <p class="text-xs text-gray-400 mt-1">{{ $stats['today_count'] }} transactions</p>
        </div>
        <div class="bg-white shadow rounded-lg p-5">
            <dt class="text-sm text-gray-500">This Month</dt>
            <dd class="text-2xl font-bold text-brand-600 mt-1">TZS {{ number_format($stats['month_revenue']) }}</dd>
            <p class="text-xs text-gray-400 mt-1">{{ $stats['month_count'] }} transactions</p>
        </div>
        <div class="bg-white shadow rounded-lg p-5">
            <dt class="text-sm text-gray-500">All-Time Revenue</dt>
            <dd class="text-2xl font-bold text-gray-900 mt-1">TZS {{ number_format($stats['total_revenue']) }}</dd>
        </div>
        <div class="bg-white shadow rounded-lg p-5">
            <dt class="text-sm text-gray-500">Pending / Failed</dt>
            <dd class="text-2xl font-bold text-yellow-600 mt-1">{{ $stats['pending_count'] }} <span class="text-red-500 text-lg">/ {{ $stats['failed_count'] }}</span></dd>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white shadow rounded-lg p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Phone, reference, name..." class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="status" class="rounded-md border-gray-300 text-sm px-3 py-2 border">
                    <option value="">All</option>
                    @foreach(['pending','completed','failed','cancelled','expired'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Operator</label>
                <select name="operator" class="rounded-md border-gray-300 text-sm px-3 py-2 border">
                    <option value="">All</option>
                    @foreach($operators as $op)
                        <option value="{{ $op }}" {{ request('operator') === $op ? 'selected' : '' }}>{{ strtoupper($op) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
                <select name="payment_type" class="rounded-md border-gray-300 text-sm px-3 py-2 border">
                    <option value="">All</option>
                    @foreach(['subscription','coin_purchase','episode_unlock'] as $t)
                        <option value="{{ $t }}" {{ request('payment_type') === $t ? 'selected' : '' }}>{{ str_replace('_',' ',ucfirst($t)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-md border-gray-300 text-sm px-3 py-2 border">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-md border-gray-300 text-sm px-3 py-2 border">
            </div>
            <div>
                <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-brand-700">Filter</button>
                <a href="{{ route('admin.transactions.index') }}" class="ml-2 text-sm text-gray-500 hover:text-gray-700">Clear</a>
            </div>
        </form>
    </div>

    {{-- Transactions Table --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operator</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($transactions as $tx)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-mono text-gray-600">{{ Str::limit($tx->reference, 15) }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($tx->user)
                                <a href="{{ route('admin.users.show', $tx->user_id) }}" class="text-brand-600 hover:underline">{{ $tx->user->name }}</a>
                            @else
                                <span class="text-gray-400">Guest</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $tx->phone }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 uppercase">{{ $tx->operator ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $tx->currency ?? 'TZS' }} {{ number_format($tx->amount) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ str_replace('_', ' ', $tx->payment_type ?? '—') }}</td>
                        <td class="px-4 py-3">
                            @php
                                $colors = ['completed' => 'green', 'pending' => 'yellow', 'failed' => 'red', 'cancelled' => 'gray', 'expired' => 'gray'];
                                $c = $colors[$tx->status] ?? 'gray';
                            @endphp
                            <span class="inline-flex rounded-full bg-{{ $c }}-100 px-2 py-1 text-xs font-medium text-{{ $c }}-800">{{ ucfirst($tx->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $tx->created_at->format('M d, H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.transactions.show', $tx) }}" class="text-brand-600 hover:text-brand-900 text-sm font-medium">Details</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-gray-500">No transactions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">{{ $transactions->links() }}</div>
</div>
@endsection
