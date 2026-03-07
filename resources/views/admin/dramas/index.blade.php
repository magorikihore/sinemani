@extends('admin.layouts.app')

@section('title', 'Dramas')
@section('header', 'Dramas')

@section('content')
<div class="space-y-4">
    {{-- Toolbar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <form method="GET" action="{{ route('admin.dramas.index') }}" class="flex flex-wrap items-center gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search dramas..."
                class="rounded-md border-gray-300 shadow-sm text-sm focus:border-brand-500 focus:ring-brand-500 w-64">
            <select name="status" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="">All Status</option>
                @foreach(['draft','published','completed','suspended'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select name="category_id" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Filter</button>
            @if(request()->hasAny(['search','status','category_id']))
                <a href="{{ route('admin.dramas.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
        </form>
        <a href="{{ route('admin.dramas.create') }}" class="inline-flex items-center rounded-md bg-brand-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500">
            <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/></svg>
            Add Drama
        </a>
    </div>

    {{-- Table --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Drama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Episodes</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Views</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($dramas as $drama)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            @if($drama->cover_image)
                                <img src="{{ Storage::url($drama->cover_image) }}" alt="" class="h-12 w-9 rounded object-cover mr-3 flex-shrink-0">
                            @else
                                <div class="h-12 w-9 rounded bg-gray-200 mr-3 flex-shrink-0 flex items-center justify-center">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @endif
                            <div>
                                <a href="{{ route('admin.dramas.show', $drama) }}" class="text-sm font-medium text-gray-900 hover:text-brand-600">{{ $drama->title }}</a>
                                <p class="text-xs text-gray-500">{{ Str::limit($drama->synopsis, 60) }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $drama->category?->name ?? '—' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">{{ $drama->published_episodes }}/{{ $drama->total_episodes }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">{{ number_format($drama->view_count) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                            {{ $drama->status === 'published' ? 'bg-green-50 text-green-700 ring-1 ring-green-600/20' :
                               ($drama->status === 'draft' ? 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-600/20' :
                               ($drama->status === 'completed' ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20' :
                               'bg-red-50 text-red-700 ring-1 ring-red-600/20')) }}">
                            {{ ucfirst($drama->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                        <a href="{{ route('admin.dramas.edit', $drama) }}" class="text-brand-600 hover:text-brand-900 mr-3">Edit</a>
                        <a href="{{ route('admin.dramas.show', $drama) }}" class="text-gray-600 hover:text-gray-900">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                        No dramas found.
                        <a href="{{ route('admin.dramas.create') }}" class="text-brand-600 hover:text-brand-800 font-medium">Create your first drama</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($dramas->hasPages())
    <div class="bg-white px-4 py-3 rounded-lg shadow">
        {{ $dramas->links() }}
    </div>
    @endif
</div>
@endsection
