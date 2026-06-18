<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'parent_shop_id', 'contact_id',
        'valid', 'payment_method', 'invoice_number',
    ];

    protected $casts = [
        'valid' => 'boolean',
        'payment_method' => PaymentMethod::class,
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'parent_shop_id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function purchasedItems()
    {
        return $this->hasMany(PurchasedItem::class, 'purchase_id');
    }

    /**
     * Total net price in grosz (sum of purchased items net prices).
     */
    public function getTotalNetPrice(): int
    {
        return (int) $this->purchasedItems->sum('price');
    }

    /**
     * Total gross price in grosz (sum of purchased items gross prices).
     */
    public function getTotalGrossPrice(): int
    {
        return (int) $this->purchasedItems->sum(fn (PurchasedItem $pi) => $pi->getGrossPrice());
    }

    /**
     * Number of items in this purchase.
     */
    public function getItemsCount(): int
    {
        return $this->purchasedItems->count();
    }
}