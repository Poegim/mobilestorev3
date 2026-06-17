<?php

namespace App\Models;

use App\Enums\ItemStatus;
use Illuminate\Database\Eloquent\Model;

class SoldItem extends Model
{
    public $timestamps = false;
    protected $table = 'sells_items';
    protected $fillable = [
        'sell_id', 'item_id', 'service_id',
        'price', 'internal_cost', 'tax_id', 'valid',
    ];

    protected $casts = [
        'valid' => 'boolean',
    ];

    public function sell()
    {
        return $this->belongsTo(Sell::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    public function getIncome(): int
    {
        $boughtFor = 0;

        if ($this->item_id) {
            $boughtFor = $this->item->purchasedItem?->getGrossPrice() ?? 0;
        } else {
            $boughtFor = $this->internal_cost;
        }

        return $this->price - $boughtFor;
    }

    public function invalidate(): void
    {
        $this->update(['valid' => 0]);

        if ($this->item_id) {
            $this->item->update(['status' => ItemStatus::Store]);
        }
    }
}