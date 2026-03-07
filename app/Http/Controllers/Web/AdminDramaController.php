<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Drama;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminDramaController extends Controller
{
    public function index(Request $request)
    {
        $query = Drama::with(['category', 'tags']);

        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $dramas = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('admin.dramas.index', compact('dramas', 'categories'));
    }

    public function create()
    {
        $categories = Category::active()->orderBy('name')->get();
        $tags = Tag::where('is_active', true)->orderBy('name')->get();

        return view('admin.dramas.create', compact('categories', 'tags'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'synopsis' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'content_rating' => ['nullable', 'string', 'in:G,PG,PG-13,R,NC-17'],
            'language' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:100'],
            'release_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'director' => ['nullable', 'string', 'max:255'],
            'cast' => ['nullable', 'string'],
            'trailer_url' => ['nullable', 'url'],
            'is_featured' => ['sometimes'],
            'is_trending' => ['sometimes'],
            'is_new_release' => ['sometimes'],
            'is_free' => ['sometimes'],
            'coin_price' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,published,completed,suspended'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'banner_image' => ['nullable', 'image', 'max:10240'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
        ]);

        $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_trending'] = $request->boolean('is_trending');
        $data['is_new_release'] = $request->boolean('is_new_release');
        $data['is_free'] = $request->boolean('is_free');

        // Parse cast as JSON array
        if (!empty($data['cast'])) {
            $data['cast'] = array_map('trim', explode(',', $data['cast']));
        }

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('dramas/covers', 'public');
        }

        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $request->file('banner_image')->store('dramas/banners', 'public');
        }

        if (isset($data['status']) && $data['status'] === 'published') {
            $data['published_at'] = now();
        }

        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $drama = Drama::create($data);

        if (!empty($tags)) {
            $drama->tags()->sync($tags);
        }

        return redirect()->route('admin.dramas.show', $drama)
            ->with('success', 'Drama "' . $drama->title . '" created successfully.');
    }

    public function show(Drama $drama)
    {
        $drama->load(['category', 'tags', 'episodes' => function ($q) {
            $q->orderBy('season_number')->orderBy('episode_number');
        }]);

        return view('admin.dramas.show', compact('drama'));
    }

    public function edit(Drama $drama)
    {
        $drama->load('tags');
        $categories = Category::active()->orderBy('name')->get();
        $tags = Tag::where('is_active', true)->orderBy('name')->get();

        return view('admin.dramas.edit', compact('drama', 'categories', 'tags'));
    }

    public function update(Request $request, Drama $drama)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'synopsis' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'content_rating' => ['nullable', 'string', 'in:G,PG,PG-13,R,NC-17'],
            'language' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:100'],
            'release_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'director' => ['nullable', 'string', 'max:255'],
            'cast' => ['nullable', 'string'],
            'trailer_url' => ['nullable', 'url'],
            'is_featured' => ['sometimes'],
            'is_trending' => ['sometimes'],
            'is_new_release' => ['sometimes'],
            'is_free' => ['sometimes'],
            'coin_price' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,published,completed,suspended'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'banner_image' => ['nullable', 'image', 'max:10240'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
        ]);

        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_trending'] = $request->boolean('is_trending');
        $data['is_new_release'] = $request->boolean('is_new_release');
        $data['is_free'] = $request->boolean('is_free');

        if (!empty($data['cast'])) {
            $data['cast'] = array_map('trim', explode(',', $data['cast']));
        } else {
            $data['cast'] = null;
        }

        if ($request->hasFile('cover_image')) {
            if ($drama->cover_image) {
                Storage::disk('public')->delete($drama->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('dramas/covers', 'public');
        }

        if ($request->hasFile('banner_image')) {
            if ($drama->banner_image) {
                Storage::disk('public')->delete($drama->banner_image);
            }
            $data['banner_image'] = $request->file('banner_image')->store('dramas/banners', 'public');
        }

        if (isset($data['status']) && $data['status'] === 'published' && !$drama->published_at) {
            $data['published_at'] = now();
        }

        $drama->tags()->sync($data['tags'] ?? []);
        unset($data['tags']);

        $drama->update($data);

        return redirect()->route('admin.dramas.show', $drama)
            ->with('success', 'Drama updated successfully.');
    }

    public function destroy(Drama $drama)
    {
        $title = $drama->title;
        $drama->delete();

        return redirect()->route('admin.dramas.index')
            ->with('success', "Drama \"{$title}\" deleted successfully.");
    }
}
