<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchasedItem extends Model
{
    public $timestamps = false;
    protected $table = 'purchases_items';
    protected $fillable = ['purchase_id', 'item_id', 'tax_id', 'price'];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    public function getGrossPrice(): int
    {
        $tax = $this->tax;
        $multiplier = $tax ? (1 + $tax->getReal()) : 1;
        return (int) ($this->price * $multiplier);
    }
}