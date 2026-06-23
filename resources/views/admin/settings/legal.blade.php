@extends('admin.layouts.app')
@section('title', 'Legal Pages')
@section('header', 'Legal Pages')

@section('content')
<div class="max-w-5xl space-y-6">
    <div class="bg-white shadow rounded-lg p-6">
        <p class="text-sm text-gray-500">
            Edit the public Privacy Policy, Terms of Service, and Account Deletion pages. HTML tags such as
            <code class="text-xs bg-gray-100 px-1 rounded">&lt;h2&gt;</code>,
            <code class="text-xs bg-gray-100 px-1 rounded">&lt;p&gt;</code>, and
            <code class="text-xs bg-gray-100 px-1 rounded">&lt;ul&gt;</code> are supported.
        </p>
        <div class="mt-3 flex gap-4 text-sm">
            <a href="{{ route('privacy') }}" target="_blank" class="text-brand-600 hover:text-brand-800">Preview Privacy Policy ↗</a>
            <a href="{{ route('terms') }}" target="_blank" class="text-brand-600 hover:text-brand-800">Preview Terms of Service ↗</a>
            <a href="{{ route('account-deletion') }}" target="_blank" class="text-brand-600 hover:text-brand-800">Preview Account Deletion ↗</a>
            <a href="{{ route('data-deletion') }}" target="_blank" class="text-brand-600 hover:text-brand-800">Preview Data Deletion ↗</a>
        </div>
        <div class="mt-3 space-y-1 text-sm text-gray-600">
            <p><strong>Google Play — Account deletion URL:</strong> <code class="text-xs bg-gray-100 px-1 rounded">{{ route('account-deletion') }}</code></p>
            <p><strong>Google Play — Data deletion URL:</strong> <code class="text-xs bg-gray-100 px-1 rounded">{{ route('data-deletion') }}</code></p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.settings.legal.update') }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Privacy Policy</h3>
            </div>
            <div class="px-6 py-4">
                <textarea name="privacy_policy_content" rows="18" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border font-mono">{{ old('privacy_policy_content', $privacyContent) }}</textarea>
                @error('privacy_policy_content')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Terms of Service</h3>
            </div>
            <div class="px-6 py-4">
                <textarea name="terms_of_service_content" rows="18" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border font-mono">{{ old('terms_of_service_content', $termsContent) }}</textarea>
                @error('terms_of_service_content')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Account &amp; Data Deletion</h3>
                <p class="text-xs text-gray-500 mt-1">Required by Google Play — link users can use to request account deletion.</p>
            </div>
            <div class="px-6 py-4">
                <textarea name="account_deletion_content" rows="18" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border font-mono">{{ old('account_deletion_content', $accountDeletionContent) }}</textarea>
                @error('account_deletion_content')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Data Deletion Request</h3>
                <p class="text-xs text-gray-500 mt-1">For users who want to delete specific data without closing their account.</p>
            </div>
            <div class="px-6 py-4">
                <textarea name="data_deletion_content" rows="18" class="w-full rounded-md border-gray-300 text-sm px-3 py-2 border font-mono">{{ old('data_deletion_content', $dataDeletionContent) }}</textarea>
                @error('data_deletion_content')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-brand-600 text-white px-6 py-2 rounded-md text-sm font-medium hover:bg-brand-700">
                Save Legal Pages
            </button>
        </div>
    </form>
</div>
@endsection
