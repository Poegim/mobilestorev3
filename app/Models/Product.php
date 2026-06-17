<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public $timestamps = false;
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
}