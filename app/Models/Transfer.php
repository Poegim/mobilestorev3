<?php

namespace App\Models;

use App\Enums\TransferStatus;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'parent_shop_id', 'target_shop_id',
        'added_timestamp', 'finished_timestamp', 'status',
    ];

    protected $casts = [
        'status' => TransferStatus::class,
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