<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PurchaseStatus;
use App\Enums\PurchaseType;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'parent_shop_id', 'contact_id',
        'valid', 'payment_method', 'invoice_number',
        'is_draft', 'created_by', 'purchase_type',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected $casts = [
        'valid' => 'boolean',
        'payment_method' => PaymentMethod::class,
        'purchase_type' => PurchaseType::class,
        'is_draft' => 'boolean',
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

    /** Derived status: a draft is never "completed", even though valid = 1. */
    public function status(): PurchaseStatus
    {
        return match (true) {
            $this->is_draft => PurchaseStatus::Draft,
            ! $this->valid  => PurchaseStatus::Cancelled,
            default         => PurchaseStatus::Completed,
        };
    }

    public function scopeWithStatus($query, PurchaseStatus $status)
    {
        return match ($status) {
            PurchaseStatus::Draft     => $query->where('is_draft', true),
            PurchaseStatus::Completed => $query->where('is_draft', false)->where('valid', true),
            PurchaseStatus::Cancelled => $query->where('is_draft', false)->where('valid', false),
        };
    }
}