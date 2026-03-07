@extends('admin.layouts.app')
@section('title', 'Payment Gateway')
@section('header', 'Payment Gateway Configuration')

@section('content')
<div class="max-w-3xl space-y-6">
    <div class="bg-white shadow rounded-lg p-6">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">payin.co.tz Gateway Settings</h3>
            <p class="text-sm text-gray-500 mt-1">Configure your mobile money payment gateway credentials and URLs.</p>
        </div>

        <form method="POST" action="{{ route('admin.settings.payment.update') }}" class="space-y-5">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Gateway Base URL</label>
                <input type="url" name="payment_gateway_url" value="{{ $config['payment_gateway_url'] }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm px-3 py-2 border" placeholder="https://api.payin.co.tz/api/v1">
                <p class="text-xs text-gray-400 mt-1">The base URL for the payment gateway API</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                <input type="text" name="payment_gateway_api_key" value="{{ $config['payment_gateway_api_key'] }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm px-3 py-2 border font-mono" placeholder="Your API key from payin.co.tz dashboard">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">API Secret</label>
                <div x-data="{ show: false }" class="relative">
                    <input :type="show ? 'text' : 'password'" name="payment_gateway_api_secret" value="{{ $config['payment_gateway_api_secret'] }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm px-3 py-2 border font-mono pr-16" placeholder="Your API secret">
                    <button type="button" @click="show = !show" class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-gray-500 hover:text-gray-700 px-2 py-1" x-text="show ? 'Hide' : 'Show'"></button>
                </div>
                <p class="text-xs text-gray-400 mt-1">Used for HMAC-SHA256 webhook signature verification</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Callback URL</label>
                <input type="url" name="payment_callback_url" value="{{ $config['payment_callback_url'] }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm px-3 py-2 border" placeholder="https://api.sinemani.co.tz/api/payments/callback">
                <p class="text-xs text-gray-400 mt-1">The URL where payment gateway sends payment notifications. Must be publicly accessible.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Timeout (seconds)</label>
                <input type="number" name="payment_gateway_timeout" value="{{ $config['payment_gateway_timeout'] }}" min="5" max="120" class="w-32 rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm px-3 py-2 border">
                <p class="text-xs text-gray-400 mt-1">Maximum time to wait for gateway response (5-120 seconds)</p>
            </div>

            <div class="pt-4 border-t">
                <button type="submit" class="bg-brand-600 text-white px-6 py-2 rounded-md text-sm font-medium hover:bg-brand-700">
                    Save Payment Settings
                </button>
            </div>
        </form>
    </div>

    {{-- Info Box --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <svg class="h-5 w-5 text-blue-400 mt-0.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/></svg>
            <div class="ml-3">
                <h4 class="text-sm font-medium text-blue-800">Supported Operators</h4>
                <ul class="mt-1 text-sm text-blue-700 list-disc list-inside">
                    <li>M-Pesa (Vodacom) — prefix 255 67x, 68x</li>
                    <li>Tigo Pesa (Tigo) — prefix 255 65x, 71x</li>
                    <li>Airtel Money — prefix 255 78x, 68x</li>
                    <li>Halotel — prefix 255 62x</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
