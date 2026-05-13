<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Unit;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ItemController extends Controller
{
    public function index()
    {
        Gate::authorize('view-inventory'); 
        return Inertia::render('Items/Index', [
            'items' => Item::with(['category', 'unit'])->get()
        ]);
    }

    public function create()
    {
        Gate::authorize('manage-inventory');
        return Inertia::render('Items/Create', [
            'mainCategories' => Category::whereNull('parent_id')->select('id', 'name')->get(),
            'subCategories' => Category::whereNotNull('parent_id')->select('id', 'name', 'parent_id')->get(),
            'units' => Unit::select('id', 'name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_code' => 'required|unique:items,product_code',
            'serial_no'    => 'nullable|string|max:255',
            'name'         => 'required|string|max:255',
            'quantity'     => 'required|numeric|min:0',
            'min_stock'    => 'required|numeric|min:0',
            'category_id'  => 'required|exists:categories,id',
            'unit_id'      => 'nullable|exists:units,id',
            'description'  => 'nullable|string'
        ]);

        $data = collect($validated)->except('category_id')->toArray();
        $data['serial_no'] = $request->serial_no ?? '0';

        $item = Item::create($data);
        $item->category()->attach($request->category_id);

        return redirect()->route('web.items.index')->with('success', 'Item registered successfully!');
    }

    public function show(Item $item)
    {
        Gate::authorize('view-inventory');
        return Inertia::render('Items/Show', [
            'item' => $item->load(['category', 'unit'])
        ]);
    }

    public function edit(Item $item)
    {
        Gate::authorize('manage-inventory');
        
        return Inertia::render('Items/Edit', [
            'item' => $item->load('category'),
            'mainCategories' => Category::whereNull('parent_id')->select('id', 'name')->get(),
            'subCategories' => Category::whereNotNull('parent_id')->select('id', 'name', 'parent_id')->get(),
            'units' => Unit::select('id', 'name')->get()
        ]);
    }

    public function update(Request $request, Item $item)
    {
        Gate::authorize('manage-inventory');
        $validated = $request->validate([
            'product_code' => 'required|string|max:255|unique:items,product_code,' . $item->id,
            'name'         => 'required|string|max:255',
            'min_stock'    => 'required|numeric|min:0',
            'unit_id'      => 'nullable|exists:units,id',
            'category_id'  => 'nullable|exists:categories,id',
            'description'  => 'nullable|string'
        ]);

        $item->update(collect($validated)->except('category_id')->toArray());
        
        if ($request->category_id) {
            $item->category()->sync([$request->category_id]);
        }

        return redirect()->route('web.items.index')->with('success', 'Item updated successfully.');
    }

    public function destroy(Item $item)
    {
        Gate::authorize('delete-inventory');
        $item->delete();
        return redirect()->route('web.items.index')->with('success', 'Item deleted.');
    }

    public function generateProductCode(Request $request)
    {
        $targetId = $request->category_id ?: $request->main_category_id;
        if (!$targetId) return response()->json(['next_code' => '']);

        $category = Category::findOrFail($targetId);
        $prefix = strtoupper(str_replace(' ', '', $category->name)); 

        $latestItem = Item::whereHas('category', function($q) use ($targetId) {
            $q->where('categories.id', $targetId);
        })->latest('id')->first();

        $nextNumber = 1;
        if ($latestItem && str_contains($latestItem->product_code, '-')) {
            $parts = explode('-', $latestItem->product_code);
            $lastPart = end($parts);
            $nextNumber = is_numeric($lastPart) ? intval($lastPart) + 1 : 1;
        }

        return response()->json([
            'next_code' => $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT)
        ]);
    }
}