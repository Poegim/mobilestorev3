<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'created_by', 'ean'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function devices()
    {
        return $this->belongsToMany(Device::class, 'variant_device', 'variant_id', 'device_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(Item::class, 'variant_id');
    }

    /** e.g. "Galaxy A37" or "Galaxy A16/A26" or "Uniwersalny" */
    public function getDisplayNameAttribute(): string
    {
        $deviceNames = $this->devices->pluck('full_name')->join('/');

        return $deviceNames ?: 'Uniwersalny';
    }

    /** e.g. "ROAR Cloud Skin Galaxy A37" */
    public function getFullDisplayNameAttribute(): string
    {
        $product = $this->product;
        $parts = [];

        if ($product?->brand) {
            $parts[] = $product->brand->name;
        }

        $parts[] = $product?->name ?? '';

        $deviceNames = $this->devices->pluck('full_name')->join('/');
        if ($deviceNames) {
            $parts[] = $deviceNames;
        }

        return implode(' ', $parts);
    }
}