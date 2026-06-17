<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'parent_shop_id', 'contact_id', 'added_timestamp',
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
}