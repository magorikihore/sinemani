@extends('admin.layouts.app')
@section('title', $user->name)
@section('header', 'User Details')

@section('content')
<div class="space-y-6">
    {{-- Back --}}
    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        Back to Users
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- User Profile Card --}}
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center space-x-4 mb-6">
                @if($user->avatar)
                    <img class="h-16 w-16 rounded-full object-cover" src="{{ asset('storage/' . $user->avatar) }}" alt="">
                @else
                    <div class="h-16 w-16 rounded-full bg-brand-100 flex items-center justify-center text-brand-600 font-bold text-xl">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                @endif
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ $user->name }}</h3>
                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                    @if($user->username) <p class="text-sm text-gray-400">@{{ $user->username }}</p> @endif
                </div>
            </div>

            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Phone</dt><dd class="font-medium">{{ $user->phone ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Coins</dt><dd class="font-bold text-brand-600">{{ number_format($user->coin_balance) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Joined</dt><dd>{{ $user->created_at->format('M d, Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Last Login</dt><dd>{{ $user->last_login_at?->diffForHumans() ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Roles</dt><dd>{{ $user->getRoleNames()->join(', ') ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Provider</dt><dd>{{ $user->provider ?? 'email' }}</dd></div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Status</dt>
                    <dd>
                        @if($user->is_active)
                            <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">Active</span>
                        @else
                            <span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800">Banned</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">VIP</dt>
                    <dd>
                        @if($user->is_vip && $user->vip_expires_at?->isFuture())
                            <span class="inline-flex rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">VIP until {{ $user->vip_expires_at->format('M d, Y') }}</span>
                        @else
                            <span class="text-gray-400">No</span>
                        @endif
                    </dd>
                </div>
            </dl>

            <div class="mt-6 space-y-3">
                {{-- Ban/Unban --}}
                <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="w-full text-center px-4 py-2 rounded-md text-sm font-medium {{ $user->is_active ? 'bg-red-50 text-red-700 hover:bg-red-100' : 'bg-green-50 text-green-700 hover:bg-green-100' }}" onclick="return confirm('{{ $user->is_active ? 'Ban this user?' : 'Unban this user?' }}')">
                        {{ $user->is_active ? 'Ban User' : 'Unban User' }}
                    </button>
                </form>

                {{-- VIP Toggle --}}
                <div x-data="{ showVip: false }">
                    @if($user->is_vip && $user->vip_expires_at?->isFuture())
                        <form method="POST" action="{{ route('admin.users.toggle-vip', $user) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="days" value="0">
                            <button type="submit" class="w-full text-center px-4 py-2 rounded-md text-sm font-medium bg-yellow-50 text-yellow-700 hover:bg-yellow-100" onclick="return confirm('Remove VIP?')">
                                Remove VIP
                            </button>
                        </form>
                    @else
                        <button @click="showVip = !showVip" class="w-full text-center px-4 py-2 rounded-md text-sm font-medium bg-yellow-50 text-yellow-700 hover:bg-yellow-100">
                            Grant VIP
                        </button>
                        <form x-show="showVip" x-cloak method="POST" action="{{ route('admin.users.toggle-vip', $user) }}" class="mt-2 flex gap-2">
                            @csrf @method('PATCH')
                            <input type="number" name="days" min="1" max="365" value="30" class="flex-1 rounded-md border-gray-300 text-sm px-3 py-2 border" placeholder="Days">
                            <button type="submit" class="bg-yellow-600 text-white px-3 py-2 rounded-md text-sm">Grant</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Stats & Actions --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Stats Grid --}}
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                @foreach([
                    ['Episodes Watched', $stats['episodes_watched']],
                    ['Unlocked', $stats['episodes_unlocked']],
                    ['Coins Spent', number_format($stats['coins_spent'])],
                    ['Comments', $stats['comments_count']],
                    ['Ratings', $stats['ratings_count']],
                ] as [$label, $value])
                <div class="bg-white shadow rounded-lg p-4 text-center">
                    <dt class="text-xs text-gray-500">{{ $label }}</dt>
                    <dd class="text-xl font-bold text-gray-900 mt-1">{{ $value }}</dd>
                </div>
                @endforeach
            </div>

            {{-- Grant/Deduct Coins --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Manage Coins</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <form method="POST" action="{{ route('admin.users.grant-coins', $user) }}" class="space-y-3">
                        @csrf
                        <input type="number" name="amount" min="1" required class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border" placeholder="Amount">
                        <input type="text" name="reason" required class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border" placeholder="Reason">
                        <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-700">Grant Coins</button>
                    </form>
                    <form method="POST" action="{{ route('admin.users.deduct-coins', $user) }}" class="space-y-3">
                        @csrf
                        <input type="number" name="amount" min="1" required class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border" placeholder="Amount">
                        <input type="text" name="reason" required class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border" placeholder="Reason">
                        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700">Deduct Coins</button>
                    </form>
                </div>
            </div>

            {{-- Active Subscription --}}
            @if($activeSubscription)
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Active Subscription</h3>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium">{{ $activeSubscription->plan?->name ?? 'Unknown Plan' }}</p>
                        <p class="text-sm text-gray-500">Expires: {{ $activeSubscription->ends_at?->format('M d, Y') }}</p>
                        <p class="text-sm text-gray-500">Amount: TZS {{ number_format($activeSubscription->amount_paid) }}</p>
                    </div>
                    <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800">{{ ucfirst($activeSubscription->status) }}</span>
                </div>
            </div>
            @endif

            {{-- Recent Payments --}}
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900">Recent Payments</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($recentPayments as $payment)
                        <tr>
                            <td class="px-4 py-3 text-sm font-mono text-gray-600">{{ Str::limit($payment->reference, 15) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $payment->phone }}</td>
                            <td class="px-4 py-3 text-sm font-medium">TZS {{ number_format($payment->amount) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $payment->status === 'completed' ? 'bg-green-100 text-green-800' : ($payment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $payment->created_at->format('M d, H:i') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No payments yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Coin Transactions --}}
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900">Recent Coin Transactions</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($coinTransactions as $tx)
                        <tr>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $tx->type === 'credit' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($tx->type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium {{ $tx->type === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $tx->type === 'credit' ? '+' : '-' }}{{ number_format($tx->amount) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ str_replace('_', ' ', $tx->source) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ Str::limit($tx->description, 40) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ number_format($tx->balance_after) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $tx->created_at->format('M d, H:i') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No coin transactions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
