<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;

class Sell extends Model
{
    
    
    protected $fillable = [
        'parent_shop_id', 'valid', 'payment_method',
    ];

    protected $casts = [
        'valid' => 'boolean',
        'payment_method' => PaymentMethod::class,
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'parent_shop_id');
    }

    public function soldItems()
    {
        return $this->hasMany(SoldItem::class, 'sell_id');
    }

    public function getTotalPrice(): int
    {
        return $this->soldItems->where('valid', 1)->sum('price');
    }

    public function getTotalIncome(): int
    {
        return $this->soldItems->where('valid', 1)->sum(fn ($si) => $si->getIncome());
    }

    public function invalidate(): void
    {
        $this->update(['valid' => 0]);

        foreach ($this->soldItems as $soldItem) {
            if ($soldItem->item_id) {
                $soldItem->item->update(['status' => ItemStatus::Store]);
            }
        }
    }
}