<?php

namespace App\Models;

use App\Enums\TransferStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Transfer extends Model
{
    protected $fillable = [
        'parent_shop_id', 'target_shop_id',
        'created_by', 'finished_by',
        'finished_at', 'status',
    ];

    protected $casts = [
        'status'      => TransferStatus::class,
        'finished_at' => 'datetime',
    ];

    // -- Relationships --

    public function sourceShop()
    {
        return $this->belongsTo(Shop::class, 'parent_shop_id');
    }

    public function targetShop()
    {
        return $this->belongsTo(Shop::class, 'target_shop_id');
    }

    /** User who created the transfer and added its items. */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** User who received, canceled or marked the transfer as lost. */
    public function finisher()
    {
        return $this->belongsTo(User::class, 'finished_by');
    }

    public function transferItems()
    {
        return $this->hasMany(TransferItem::class);
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'transfers_items', 'transfer_id', 'item_id');
    }

    /**
     * Active transfers waiting to be received by the given user.
     * Inside a shop: only that shop. Without a shop: all shops of the user (admin: all).
     */
    public function scopeAwaitingReceiptFor(Builder $query, User $user, ?Shop $shop = null): Builder
    {
        $query->where('status', TransferStatus::Active->value);

        if ($shop) {
            return $query->where('target_shop_id', $shop->id);
        }

        if (! $user->isAdmin()) {
            $query->whereIn('target_shop_id', $user->shops()->pluck('shops.id'));
        }

        return $query;
    }
}