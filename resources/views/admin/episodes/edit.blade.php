@extends('admin.layouts.app')

@section('title', 'Edit: ' . $episode->title)
@section('header')
    <span class="text-gray-400">{{ $drama->title }}</span> / Edit Episode
@endsection

@section('content')
<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ route('admin.episodes.update', [$drama, $episode]) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6 space-y-6">
                <h3 class="text-base font-semibold text-gray-900 border-b pb-3">Episode Details</h3>

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" value="{{ old('title', $episode->title) }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">{{ old('description', $episode->description) }}</textarea>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div>
                        <label for="season_number" class="block text-sm font-medium text-gray-700">Season</label>
                        <input type="number" name="season_number" id="season_number" value="{{ old('season_number', $episode->season_number) }}" min="1" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="episode_number" class="block text-sm font-medium text-gray-700">Episode #</label>
                        <input type="number" name="episode_number" id="episode_number" value="{{ old('episode_number', $episode->episode_number) }}" min="1" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="duration" class="block text-sm font-medium text-gray-700">Duration (seconds)</label>
                        <input type="number" name="duration" id="duration" value="{{ old('duration', $episode->duration) }}" min="0"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                            @foreach(['draft','processing','published','failed'] as $s)
                                <option value="{{ $s }}" {{ old('status', $episode->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="coin_price" class="block text-sm font-medium text-gray-700">Coin Price</label>
                        <input type="number" name="coin_price" id="coin_price" value="{{ old('coin_price', $episode->coin_price) }}" min="0"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>
                </div>

                <label class="inline-flex items-center">
                    <input type="checkbox" name="is_free" value="1" {{ old('is_free', $episode->is_free) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    <span class="ml-2 text-sm text-gray-700">Free episode</span>
                </label>
            </div>
        </div>

        {{-- Media --}}
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6 space-y-6">
                <h3 class="text-base font-semibold text-gray-900 border-b pb-3">Media</h3>

                <div x-data="{ preview: '{{ $episode->thumbnail ? Storage::url($episode->thumbnail) : '' }}' }">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Thumbnail</label>
                    <div class="flex items-center gap-4">
                        <template x-if="preview">
                            <img :src="preview" class="h-20 w-32 rounded object-cover shadow">
                        </template>
                        <label class="cursor-pointer rounded-md border border-dashed border-gray-300 px-6 py-4 text-center hover:border-brand-400 flex-1">
                            <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="mt-1 block text-xs text-gray-500">Change thumbnail (max 5MB)</span>
                            <input type="file" name="thumbnail" accept="image/*" class="hidden" @change="preview = URL.createObjectURL($event.target.files[0])">
                        </label>
                    </div>
                </div>

                {{-- Current Video Info --}}
                @if($episode->video_path || $episode->video_url || $episode->hls_url)
                <div class="rounded-md bg-blue-50 p-3">
                    <p class="text-sm text-blue-700">
                        @if($episode->video_path)
                            <strong>Current:</strong> Uploaded video ({{ $episode->file_size ? number_format($episode->file_size / 1048576, 1) . ' MB' : 'Size unknown' }})
                        @elseif($episode->hls_url)
                            <strong>Current:</strong> HLS Stream
                        @elseif($episode->video_url)
                            <strong>Current:</strong> External URL
                        @endif
                    </p>
                </div>
                @endif

                <div x-data="{ fileName: '', fileSize: '' }">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Replace Video File</label>
                    <label class="cursor-pointer rounded-md border border-dashed border-gray-300 px-6 py-6 text-center hover:border-brand-400 block">
                        <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/></svg>
                        <span class="mt-2 block text-sm text-gray-600" x-show="!fileName">Click to upload new video</span>
                        <span class="mt-2 block text-sm text-brand-600 font-medium" x-show="fileName" x-text="fileName + ' (' + fileSize + ')'"></span>
                        <span class="mt-1 block text-xs text-gray-400">MP4, MOV, AVI, MKV, WebM — max 500MB</span>
                        <input type="file" name="video" accept="video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm" class="hidden"
                            @change="fileName = $event.target.files[0]?.name; fileSize = ($event.target.files[0]?.size / 1048576).toFixed(1) + ' MB'">
                    </label>
                </div>

                <div class="relative">
                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
                    <div class="relative flex justify-center"><span class="bg-white px-3 text-xs text-gray-500">Or provide URL</span></div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="video_url" class="block text-sm font-medium text-gray-700">Video URL</label>
                        <input type="url" name="video_url" id="video_url" value="{{ old('video_url', $episode->video_url) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="hls_url" class="block text-sm font-medium text-gray-700">HLS Stream URL</label>
                        <input type="url" name="hls_url" id="hls_url" value="{{ old('hls_url', $episode->hls_url) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <div>
                <button type="button" class="text-sm font-semibold text-red-600 hover:text-red-800"
                    onclick="if(confirm('Delete this episode? This cannot be undone.')) document.getElementById('delete-episode-form').submit()">
                    Delete Episode
                </button>
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.dramas.show', $drama) }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">Cancel</a>
                <button type="submit" class="rounded-md bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-500">
                    Save Changes
                </button>
            </div>
        </div>
    </form>

    {{-- Separate delete form (outside the edit form to avoid nesting) --}}
    <form id="delete-episode-form" method="POST" action="{{ route('admin.episodes.destroy', [$drama, $episode]) }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection
