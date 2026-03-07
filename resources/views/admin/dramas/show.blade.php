@extends('admin.layouts.app')

@section('title', $drama->title)
@section('header', $drama->title)

@section('content')
<div class="space-y-6">
    {{-- Drama Header --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="md:flex">
            @if($drama->banner_image)
                <div class="md:w-1/3">
                    <img src="{{ Storage::url($drama->banner_image) }}" alt="{{ $drama->title }}" class="h-48 w-full object-cover md:h-full">
                </div>
            @endif
            <div class="p-6 {{ $drama->banner_image ? 'md:w-2/3' : 'w-full' }}">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-xl font-bold text-gray-900">{{ $drama->title }}</h2>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ $drama->status === 'published' ? 'bg-green-50 text-green-700 ring-1 ring-green-600/20' :
                                   ($drama->status === 'draft' ? 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-600/20' :
                                   ($drama->status === 'completed' ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20' :
                                   'bg-red-50 text-red-700 ring-1 ring-red-600/20')) }}">
                                {{ ucfirst($drama->status) }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">{{ $drama->category?->name }} &bull; {{ $drama->language ?? 'N/A' }} &bull; {{ $drama->release_year ?? 'N/A' }}</p>
                    </div>
                    <a href="{{ route('admin.dramas.edit', $drama) }}" class="rounded-md bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-500">
                        Edit Drama
                    </a>
                </div>

                @if($drama->synopsis)
                    <p class="mt-3 text-sm text-gray-600">{{ $drama->synopsis }}</p>
                @endif

                <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div>
                        <dt class="text-xs text-gray-500">Episodes</dt>
                        <dd class="text-lg font-semibold text-gray-900">{{ $drama->published_episodes }}/{{ $drama->total_episodes }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500">Views</dt>
                        <dd class="text-lg font-semibold text-gray-900">{{ number_format($drama->view_count) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500">Rating</dt>
                        <dd class="text-lg font-semibold text-gray-900">{{ $drama->rating > 0 ? number_format($drama->rating, 1) . '/5' : 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500">Likes</dt>
                        <dd class="text-lg font-semibold text-gray-900">{{ number_format($drama->like_count) }}</dd>
                    </div>
                </div>

                @if($drama->tags->count())
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach($drama->tags as $tag)
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif

                @if($drama->director || $drama->cast)
                    <div class="mt-3 text-sm text-gray-500">
                        @if($drama->director)<strong>Director:</strong> {{ $drama->director }}<br>@endif
                        @if($drama->cast)<strong>Cast:</strong> {{ is_array($drama->cast) ? implode(', ', $drama->cast) : $drama->cast }}@endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Episodes Section --}}
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900">Episodes ({{ $drama->episodes->count() }})</h3>
            <a href="{{ route('admin.episodes.create', $drama) }}" class="inline-flex items-center rounded-md bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-brand-500">
                <svg class="-ml-0.5 mr-1 h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/></svg>
                Add Episode
            </a>
        </div>

        @if($drama->episodes->count())
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Episode</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Duration</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Views</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($drama->episodes as $episode)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm text-gray-500">S{{ $episode->season_number }}E{{ $episode->episode_number }}</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            @if($episode->thumbnail)
                                <img src="{{ Storage::url($episode->thumbnail) }}" class="h-10 w-16 rounded object-cover mr-3">
                            @endif
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $episode->title }}</p>
                                @if($episode->video_url || $episode->hls_url)
                                    <p class="text-xs text-green-600">Video linked</p>
                                @elseif($episode->video_path)
                                    <p class="text-xs text-blue-600">Video uploaded</p>
                                @else
                                    <p class="text-xs text-red-500">No video</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 text-center">{{ $episode->duration ? gmdate('H:i:s', $episode->duration) : '—' }}</td>
                    <td class="px-6 py-4 text-sm text-center">
                        @if($episode->is_free)
                            <span class="text-green-600 font-medium">Free</span>
                        @else
                            <span class="text-gray-900">{{ $episode->coin_price }} coins</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                            {{ $episode->status === 'published' ? 'bg-green-50 text-green-700' : ($episode->status === 'draft' ? 'bg-yellow-50 text-yellow-700' : 'bg-gray-50 text-gray-700') }}">
                            {{ ucfirst($episode->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 text-center">{{ number_format($episode->view_count) }}</td>
                    <td class="px-6 py-4 text-right text-sm">
                        <a href="{{ route('admin.episodes.edit', [$drama, $episode]) }}" class="text-brand-600 hover:text-brand-900">Edit</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/></svg>
            <h3 class="mt-2 text-sm font-semibold text-gray-900">No episodes</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by adding the first episode.</p>
            <div class="mt-4">
                <a href="{{ route('admin.episodes.create', $drama) }}" class="inline-flex items-center rounded-md bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-500">
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/></svg>
                    Add Episode
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
