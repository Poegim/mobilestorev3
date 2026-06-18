<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferItem extends Model
{
    
    protected $table = 'transfers_items';
    protected $fillable = ['transfer_id', 'item_id'];

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}