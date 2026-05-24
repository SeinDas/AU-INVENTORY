<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        // 1. We no longer need to fetch CategoryItem
        $categories = Category::withCount('items')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'categories' => $categories
            ]);
        }

        return Inertia::render('Categories/Index', [
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        // 2. Validate 'parent_id' instead of 'category_id'
        $validated = $request->validate([
            'name' => 'required|max:100|unique:categories,name',
            'parent_id' => 'nullable|exists:categories,id' 
        ]);

        // 3. Since parent_id is in our model's $fillable array, 
        // we can just pass the validated data directly. No pivot table needed!
        $category = Category::create($validated);
        
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
        // 4. This still works perfectly because we defined the items() 
        // HasMany relationship on the updated Category model!
        if ($category->items()->count() > 0) {
            $msg = 'Cannot delete category. There are still items assigned to it.';
            if ($request->wantsJson()) {
                return response()->json(['error' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        // Optional check: Prevent deleting a Main category if it has Subcategories
        if ($category->children()->count() > 0) {
            $msg = 'Cannot delete category. It still has sub-categories.';
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