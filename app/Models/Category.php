<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    protected $fillable = ['name', 'parent_id'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
    
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    // Optional: A helper to eager load children infinitely
    public function nestedChildren(): HasMany
    {
        return $this->children()->with('nestedChildren');
    }
}