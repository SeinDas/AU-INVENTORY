<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryItem extends Model
{
    protected $table = 'category_items';
    protected $fillable = ['category_id', 'subcategory_id'];

    public function mainCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'category_items_id');
    }
}