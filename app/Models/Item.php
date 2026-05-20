<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{
    protected $fillable = [
        'product_code', 
        'serial_no', 
        'name', 
        'quantity', 
        'min_stock', 
        'unit_id', 
        'category_items_id', 
        'description'
    ];

    public function categoryItem(): BelongsTo
    {
        return $this->belongsTo(CategoryItem::class, 'category_items_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}