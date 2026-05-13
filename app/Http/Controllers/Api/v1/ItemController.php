<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Unit;
use App\Models\Category;
use Illuminate\Support\Facades\Gate;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        return Item::with(['category', 'unit'])->get();
    }

    public function productCode(Request $request)
    {
        return Item::get(['id', 'product_code', 'name', 'quantity']);
    }

    public function singleProductCode($id)
    {
        return Item::select(['id', 'quantity'])->findOrFail($id);
    }

    public function show(Item $item)
    {
        Gate::authorize('view-inventory');
        return $item->load(['category', 'unit']);
    }

    public function create()
    {
        Gate::authorize('manage-inventory');
        return [
            'mainCategories' => Category::whereNull('parent_id')->get(),
            'subCategories' => Category::whereNotNull('parent_id')->get(),
            'units' => Unit::all(),
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_code' => 'required|unique:items,product_code',
            'serial_no'    => 'nullable|string|max:255',
            'name'         => 'required|string|max:255',
            'quantity'     => 'required|numeric|min:0',
            'category_id'  => 'required|exists:categories,id',
        ]);

        $data = collect($validated)->except('category_id')->toArray();
        $data['serial_no'] = $request->serial_no ?? '0';

        $item = Item::create($data);
        $item->category()->attach($request->category_id);
        
        return $item->load(['category', 'unit']);
    }

    public function update(Request $request, Item $item)
    {
        Gate::authorize('manage-inventory');

        $validated = $request->validate([
            'product_code' => 'required|string|max:255|unique:items,product_code,' . $item->id,
            'serial_no'    => 'nullable|string|max:255',
            'name'         => 'required|string|max:255',
            'min_stock'    => 'required|numeric|min:0',
            'unit_id'      => 'nullable|exists:units,id',
            'category_id'  => 'required|exists:categories,id',
            'description'  => 'nullable|string'
        ]);

        $item->update(collect($validated)->except('category_id')->toArray());
        $item->category()->sync([$request->category_id]);

        return $item->load(['category', 'unit']);
    }

    public function destroy(Item $item)
    {
        Gate::authorize('delete-inventory');
        $item->delete();
        return ['status' => 'success'];
    }

    public function generateProductCode(Request $request)
    {
        $targetId = $request->category_id ?: $request->main_category_id;
        if (!$targetId) return ['next_code' => ''];

        $category = Category::findOrFail($targetId);
        $prefix = strtoupper(str_replace(' ', '', $category->name));

        $latest = Item::whereHas('category', function($q) use ($targetId) {
            $q->where('categories.id', $targetId);
        })->latest('id')->first();

        $nextNumber = 1;
        if ($latest && str_contains($latest->product_code, '-')) {
            $parts = explode('-', $latest->product_code);
            $nextNumber = intval(end($parts)) + 1;
        }

        return ['next_code' => $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT)];
    }
}