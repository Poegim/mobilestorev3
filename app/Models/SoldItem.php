<?php

namespace App\Models;

use App\Enums\ItemStatus;
use Illuminate\Database\Eloquent\Model;

class SoldItem extends Model
{
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

    /**
     * Selling price is stored as gross (brutto).
     * Net = gross / (1 + tax_rate). For margin sales (tax_id=0) net equals gross.
     */
    public function getNetPrice(): int
    {
        if (! $this->tax_id || ! $this->tax) {
            return $this->price;
        }

        return (int) round($this->price / (1 + $this->tax->getReal()));
    }

    /**
     * Income = sell_price (gross) - purchase_price (gross).
     * For services: sell_price - internal_cost.
     */
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