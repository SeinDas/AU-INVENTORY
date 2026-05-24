<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Item;
use Illuminate\Support\Facades\Gate;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        return Item::with(['category.parent', 'unit'])->get();
    }

    public function productCode()
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
        return $item->load(['category.parent', 'unit']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_code' => 'required|unique:items,product_code',
            'name'         => 'required|string|max:255',
            'quantity'     => 'required|numeric|min:0',
            'min_stock'    => 'required|numeric|min:0',
            'category_id'  => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id',
            'unit_id'      => 'nullable|exists:units,id'
        ]);

        $finalCategoryId = $request->subcategory_id ?: $request->category_id;

        $data = collect($validated)->except(['subcategory_id'])->toArray();
        $data['category_id'] = $finalCategoryId;
        $data['serial_no'] = $request->serial_no ?? '0';

        $item = Item::create($data);
        
        return $item->load(['category.parent', 'unit']);
    }

    public function update(Request $request, Item $item)
    {
        Gate::authorize('manage-inventory');

        $validated = $request->validate([
            'product_code' => 'required|string|max:255|unique:items,product_code,' . $item->id,
            'name'         => 'required|string|max:255',
            'min_stock'    => 'required|numeric|min:0',
            'category_id'  => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id'
        ]);

        $finalCategoryId = $request->subcategory_id ?: $request->category_id;

        $data = collect($validated)->except(['subcategory_id'])->toArray();
        $data['category_id'] = $finalCategoryId;

        $item->update($data);

        return $item->load(['category.parent', 'unit']);
    }

    public function destroy(Item $item)
    {
        Gate::authorize('delete-inventory');
        $item->delete();
        return ['status' => 'success'];
    }
}