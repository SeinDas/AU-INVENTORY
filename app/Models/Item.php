<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Item extends Model
{
    protected $fillable = [
        'product_code', 
        'serial_no', 
        'name', 
        'quantity', 
        'min_stock', 
        'unit_id', 
        'description'
    ];

    public function category(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_item');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}