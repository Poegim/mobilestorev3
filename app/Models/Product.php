<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    
    protected $fillable = ['name', 'parent_category_id', 'brand_id', 'gtin'];

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

    public function devices()
    {
        return $this->belongsToMany(Device::class, 'product_device');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_legacy', false);
    }

    public function scopeLegacy($query)
    {
        return $query->where('is_legacy', true);
    }

    /** e.g. "3mk Flexible Glass iPhone 15/iPhone 16" */
    public function getDisplayNameAttribute(): string
    {
        $parts = [];

        if ($this->brand) {
            $parts[] = $this->brand->name;
        }

        $parts[] = $this->name;

        $deviceNames = $this->devices->pluck('model')->join('/');
        if ($deviceNames) {
            $parts[] = $deviceNames;
        }

        return implode(' ', $parts);
    }
}