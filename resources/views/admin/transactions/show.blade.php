@extends('admin.layouts.app')
@section('title', 'Transaction Details')
@section('header', 'Transaction Details')

@section('content')
<div class="space-y-6">
    <a href="{{ route('admin.transactions.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        Back to Transactions
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Transaction Info --}}
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Information</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between border-b pb-2"><dt class="text-gray-500">Reference</dt><dd class="font-mono font-medium">{{ $transaction->reference }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-gray-500">Gateway Reference</dt><dd class="font-mono">{{ $transaction->gateway_reference ?? '—' }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-gray-500">Gateway TX ID</dt><dd class="font-mono">{{ $transaction->gateway_transaction_id ?? '—' }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-gray-500">Phone</dt><dd class="font-medium">{{ $transaction->phone }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-gray-500">Operator</dt><dd class="uppercase font-medium">{{ $transaction->operator ?? '—' }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-gray-500">Amount</dt><dd class="text-lg font-bold text-brand-600">{{ $transaction->currency ?? 'TZS' }} {{ number_format($transaction->amount, 2) }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-gray-500">Payment Type</dt><dd>{{ str_replace('_', ' ', ucfirst($transaction->payment_type ?? '—')) }}</dd></div>
                <div class="flex justify-between border-b pb-2">
                    <dt class="text-gray-500">Status</dt>
                    <dd>
                        @php $colors = ['completed' => 'green', 'pending' => 'yellow', 'failed' => 'red', 'cancelled' => 'gray', 'expired' => 'gray']; $c = $colors[$transaction->status] ?? 'gray'; @endphp
                        <span class="inline-flex rounded-full bg-{{ $c }}-100 px-3 py-1 text-xs font-medium text-{{ $c }}-800">{{ ucfirst($transaction->status) }}</span>
                    </dd>
                </div>
                @if($transaction->failure_reason)
                <div class="flex justify-between border-b pb-2"><dt class="text-gray-500">Failure Reason</dt><dd class="text-red-600">{{ $transaction->failure_reason }}</dd></div>
                @endif
                <div class="flex justify-between border-b pb-2"><dt class="text-gray-500">Created</dt><dd>{{ $transaction->created_at->format('M d, Y H:i:s') }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-gray-500">Completed</dt><dd>{{ $transaction->completed_at?->format('M d, Y H:i:s') ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Expires</dt><dd>{{ $transaction->expires_at?->format('M d, Y H:i:s') ?? '—' }}</dd></div>
            </dl>
        </div>

        {{-- User Info --}}
        <div class="space-y-6">
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">User</h3>
                @if($transaction->user)
                    <div class="flex items-center space-x-4">
                        <div class="h-12 w-12 rounded-full bg-brand-100 flex items-center justify-center text-brand-600 font-bold">
                            {{ strtoupper(substr($transaction->user->name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ $transaction->user->name }}</p>
                            <p class="text-sm text-gray-500">{{ $transaction->user->email }}</p>
                            <p class="text-sm text-gray-500">{{ $transaction->user->phone ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('admin.users.show', $transaction->user) }}" class="text-brand-600 hover:text-brand-900 text-sm font-medium">View User Profile →</a>
                    </div>
                @else
                    <p class="text-gray-500">Guest payment (no registered user)</p>
                @endif
            </div>

            {{-- Gateway Response --}}
            @if($transaction->gateway_response)
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Gateway Response</h3>
                <pre class="bg-gray-50 rounded-lg p-4 text-xs text-gray-700 overflow-x-auto max-h-64">{{ json_encode($transaction->gateway_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
            @endif

            {{-- Push Response --}}
            @if($transaction->push_response)
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Push Response</h3>
                <pre class="bg-gray-50 rounded-lg p-4 text-xs text-gray-700 overflow-x-auto max-h-64">{{ json_encode($transaction->push_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
