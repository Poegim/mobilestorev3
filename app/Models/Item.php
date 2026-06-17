<?php

namespace App\Models;

use App\Enums\ItemStatus;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'parent_shop_id', 'product_id', 'status',
        'feature_condition_id', 'feature_price',
        'barcode_scanned_timestamp', 'displacement_timestamp',
    ];

    protected $casts = [
        'status' => ItemStatus::class,
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'parent_shop_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function condition()
    {
        return $this->belongsTo(Condition::class, 'feature_condition_id');
    }

    public function purchasedItem()
    {
        return $this->hasOne(PurchasedItem::class, 'item_id');
    }

    public function soldItem()
    {
        return $this->hasOne(SoldItem::class, 'item_id')->where('valid', 1);
    }

    // cena w groszach — fallback: item → sklep → globalna
    public function getSellingPrice(): ?int
    {
        if ($this->feature_price) {
            return $this->feature_price;
        }

        $price = ProductPrice::where('product_id', $this->product_id)
            ->where('condition_id', $this->feature_condition_id)
            ->where('shop_id', $this->parent_shop_id)
            ->first();

        if ($price) {
            return $price->price;
        }

        $price = ProductPrice::where('product_id', $this->product_id)
            ->where('condition_id', $this->feature_condition_id)
            ->where('shop_id', 0)
            ->first();

        return $price?->price;
    }

    public function getBarcode(): string
    {
        return '1'
            . str_pad($this->parent_shop_id, 3, '0', STR_PAD_LEFT)
            . str_pad($this->id, 8, '0', STR_PAD_LEFT);
    }

    public static function findByBarcode(string $barcode): ?self
    {
        $barcode = trim($barcode, '@');
        if (!preg_match('/^1[0-9]{11}$/', $barcode)) {
            return null;
        }

        $id = (int) ltrim(substr($barcode, 4, 8), '0');
        return static::find($id);
    }
}