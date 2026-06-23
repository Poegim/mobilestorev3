<?php

namespace App\Models;

use App\Enums\ItemStatus;
use App\Enums\PaymentMethod;
use App\Enums\SellStatus;
use Illuminate\Database\Eloquent\Model;

class Sell extends Model
{
    protected $fillable = [
        'parent_shop_id', 'valid', 'payment_method', 'bill_printed_at',
    ];

    protected $casts = [
        'valid'           => 'boolean',
        'payment_method'  => PaymentMethod::class,
        'bill_printed_at' => 'datetime',
    ];

    // -- Relationships --

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'parent_shop_id');
    }

    public function soldItems()
    {
        return $this->hasMany(SoldItem::class, 'sell_id');
    }

    // -- Status --

    /** Computed status derived from valid + bill_printed_at. */
    public function status(): SellStatus
    {
        if (! $this->valid) {
            return SellStatus::Cancelled;
        }

        return $this->bill_printed_at
            ? SellStatus::Completed
            : SellStatus::NoBill;
    }

    // -- Aggregates --

    public function getTotalPrice(): int
    {
        return $this->soldItems->where('valid', 1)->sum('price');
    }

    public function getTotalIncome(): int
    {
        return $this->soldItems->where('valid', 1)->sum(fn ($si) => $si->getIncome());
    }

    // -- Actions --

    public function invalidate(): void
    {
        $this->update(['valid' => 0]);

        foreach ($this->soldItems as $soldItem) {
            if ($soldItem->item_id) {
                $soldItem->item->update(['status' => ItemStatus::Store]);
            }
        }
    }

    public function markBillPrinted(): void
    {
        $this->update(['bill_printed_at' => now()]);
    }
}