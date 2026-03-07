<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminCategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('dramas')->orderBy('sort_order')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'image', 'max:1024'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['sometimes'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')->store('categories', 'public');
        }

        Category::create($data);

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'image', 'max:1024'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['sometimes'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('icon')) {
            if ($category->icon) {
                Storage::disk('public')->delete($category->icon);
            }
            $data['icon'] = $request->file('icon')->store('categories', 'public');
        }

        $category->update($data);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->dramas()->exists()) {
            return back()->with('error', 'Cannot delete category with dramas. Move dramas first.');
        }

        if ($category->icon) {
            Storage::disk('public')->delete($category->icon);
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
