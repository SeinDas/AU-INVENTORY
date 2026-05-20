<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::withCount('items')->get();

        $categoryItems = CategoryItem::all();

        if ($request->wantsJson()) {
            return response()->json([
                'categories' => $categories,
                'categoryItems' => $categoryItems
            ]);
        }

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'categoryItems' => $categoryItems 
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:100|unique:categories,name',
            'category_id' => 'nullable|exists:categories,id' 
        ]);

        $category = Category::create([
            'name' => $validated['name']
        ]);

        if ($request->has('category_id') && $request->category_id) {
            CategoryItem::create([
                'category_id' => $request->category_id,
                'subcategory_id' => $category->id,
            ]);
        }
        
        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Category created successfully.',
                'category' => $category
            ], 201);
        }

        return redirect()->route('categories.index')->with('message', 'Category created successfully.');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|max:100|unique:categories,name,' . $category->id
        ]);

        $category->update($validated);
        
        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Category updated.',
                'category' => $category
            ]);
        }

        return redirect()->route('categories.index')->with('message', 'Category updated.');
    }

    public function destroy(Request $request, Category $category)
    {
        if ($category->items()->count() > 0) {
            $msg = 'Cannot delete category. There are still items assigned to it.';
            if ($request->wantsJson()) {
                return response()->json(['error' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        $category->delete();
        
        if ($request->wantsJson()) {
            return response()->json(['message' => 'Category deleted.']);
        }

        return redirect()->route('categories.index')->with('message', 'Category deleted.');
    }
}