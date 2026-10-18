<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = ['brand_id', 'model', 'created_by'];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_device');
    }

    /** e.g. "Apple iPhone 16" */
    public function getFullNameAttribute(): string
    {
        return trim(($this->brand?->name ?? '') . ' ' . $this->model);
    }
}