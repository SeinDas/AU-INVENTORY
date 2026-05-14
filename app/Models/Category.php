<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
// 1. Add this import
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Category extends Model
{
    protected $fillable = ['name'];

    public function categoryItemsAsSub(): HasMany
    {
        return $this->hasMany(CategoryItem::class, 'subcategory_id');
    }

    // 2. Change HasMany to HasManyThrough
    public function items(): HasManyThrough
    {
        return $this->hasManyThrough(
            Item::class, 
            CategoryItem::class, 
            'subcategory_id', 
            'category_items_id'
        );
        
    }
    
    public function categoryItemsAsMain(): HasMany
    {
        return $this->hasMany(CategoryItem::class, 'category_id');
    }
}