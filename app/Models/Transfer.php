<?php

namespace App\Models;

use App\Enums\TransferStatus;
use Illuminate\Database\Eloquent\Model;

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
}