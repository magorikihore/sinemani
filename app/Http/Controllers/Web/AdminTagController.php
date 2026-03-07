<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminTagController extends Controller
{
    public function index()
    {
        $tags = Tag::withCount('dramas')->orderBy('name')->get();

        return view('admin.tags.index', compact('tags'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:tags,name'],
        ]);

        $data['slug'] = Str::slug($data['name']);

        Tag::create($data);

        return back()->with('success', 'Tag created.');
    }

    public function update(Request $request, Tag $tag)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:tags,name,' . $tag->id],
            'is_active' => ['sometimes'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');

        $tag->update($data);

        return back()->with('success', 'Tag updated.');
    }

    public function destroy(Tag $tag)
    {
        $tag->dramas()->detach();
        $tag->delete();

        return back()->with('success', 'Tag deleted.');
    }
}
