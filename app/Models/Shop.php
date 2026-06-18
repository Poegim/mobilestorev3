<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    
    protected $fillable = [
        'name', 'email', 'phone', 'color',
        'address_city', 'address_postal_code', 'address_street',
        'address_building_number', 'address_apartment_number',
        'order', 'archive',
    ];

    protected $casts = [
        'archive' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'users_shops', 'shop_id', 'user_id');
    }

    public function items()
    {
        return $this->hasMany(Item::class, 'parent_shop_id');
    }

    public function sells()
    {
        return $this->hasMany(Sell::class, 'parent_shop_id');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'parent_shop_id');
    }

    public function transfersOut()
    {
        return $this->hasMany(Transfer::class, 'parent_shop_id');
    }

    public function transfersIn()
    {
        return $this->hasMany(Transfer::class, 'target_shop_id');
    }
}