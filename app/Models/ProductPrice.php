<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    public $timestamps = false;
    protected $fillable = ['product_id', 'condition_id', 'shop_id', 'price'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function condition()
    {
        return $this->belongsTo(Condition::class);
    }

    public function getPriceInZlotyAttribute(): float
    {
        return $this->price / 100;
    }
}