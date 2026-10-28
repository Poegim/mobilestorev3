<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'name', 'parent_category_id', 'brand_id', 'gtin',
        'is_legacy', 'created_by', 'updated_by',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'parent_category_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_legacy', false);
    }

    public function scopeLegacy($query)
    {
        return $query->where('is_legacy', true);
    }

    public function getDisplayNameAttribute(): string
    {
        $parts = [];

        if ($this->brand) {
            $parts[] = $this->brand->name;
        }

        $parts[] = $this->name;

        return implode(' ', $parts);
    }
}