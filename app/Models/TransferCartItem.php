<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TransferCartItem extends Model
{
    protected $fillable = ['user_id', 'parent_shop_id', 'item_id'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /** Cart lines of one user for one source shop. */
    public function scopeForUserInShop(Builder $query, User $user, Shop $shop): Builder
    {
        return $query
            ->where('user_id', $user->id)
            ->where('parent_shop_id', $shop->id);
    }
}