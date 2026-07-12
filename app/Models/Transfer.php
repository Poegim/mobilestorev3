<?php

namespace App\Models;

use App\Enums\TransferStatus;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    protected $fillable = [
        'parent_shop_id', 'target_shop_id',
        'finished_at', 'status',
    ];

    protected $casts = [
        'status' => TransferStatus::class,
        'finished_at' => 'datetime',
    ];

    public function sourceShop()
    {
        return $this->belongsTo(Shop::class, 'parent_shop_id');
    }

    public function targetShop()
    {
        return $this->belongsTo(Shop::class, 'target_shop_id');
    }

    public function transferItems()
    {
        return $this->hasMany(TransferItem::class);
    }
}