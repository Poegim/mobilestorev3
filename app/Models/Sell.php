<?php

namespace App\Models;

use App\Enums\ItemStatus;
use App\Enums\PaymentMethod;
use App\Enums\SellStatus;
use Illuminate\Database\Eloquent\Model;

class Sell extends Model
{
    protected $fillable = [
        'parent_shop_id', 'valid', 'payment_method', 'bill_printed_at',
    ];

    protected $casts = [
        'valid'           => 'boolean',
        'payment_method'  => PaymentMethod::class,
        'bill_printed_at' => 'datetime',
    ];

    // -- Relationships --

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'parent_shop_id');
    }

    public function soldItems()
    {
        return $this->hasMany(SoldItem::class, 'sell_id');
    }

    // -- Status --

    /** Computed status derived from valid + bill_printed_at. */
    public function status(): SellStatus
    {
        if (! $this->valid) {
            return SellStatus::Cancelled;
        }

        return $this->bill_printed_at
            ? SellStatus::Completed
            : SellStatus::NoBill;
    }

    // -- Aggregates --

    public function getTotalPrice(): int
    {
        return $this->soldItems->where('valid', 1)->sum('price');
    }

    public function getTotalIncome(): int
    {
        return $this->soldItems->where('valid', 1)->sum(fn ($si) => $si->getIncome());
    }

    // -- Actions --

    public function invalidate(): void
    {
        $this->update(['valid' => 0]);

        foreach ($this->soldItems as $soldItem) {
            if ($soldItem->item_id) {
                $soldItem->item->update(['status' => ItemStatus::Store]);
            }
        }
    }

    public function markBillPrinted(): void
    {
        $this->update(['bill_printed_at' => now()]);
    }

    /**
     * Build the JSON payload for the fiscal printer, matching the legacy protocol.
     *
     * Items are aggregated by (product/service, price, tax) — identical items
     * in the same sell become one line with quantity > 1.
     * Amounts in the payload are in PLN (złote), not grosze.
     * VAT-exempt items (komis, no tax_id) get v="zw" and "Komis " name prefix.
     *
     * @return array{bID: int, printer: int, method: string, pos: list<array>, pay: array}
     */
    public function buildPrinterPayload(): array
    {
        // Only valid items with price > 0 go on the fiscal receipt
        $validItems = $this->soldItems
            ->where('valid', true)
            ->where('price', '>', 0);

        // Aggregate items by: product_id|service_id|price|tax_percentage
        $grouped = [];
        foreach ($validItems as $si) {
            $productId = $si->item_id ? ($si->item?->product_id ?? 0) : 0;
            $serviceId = $si->service_id ?? 0;
            $taxPct    = $si->tax_id && $si->tax ? $si->tax->percentage : null;

            $key = "{$productId}|{$serviceId}|{$si->price}|{$taxPct}";
            $grouped[$key][] = $si;
        }

        $pos        = [];
        $totalPrice = 0; // grosze

        foreach ($grouped as $items) {
            $rep      = $items[0];
            $quantity = count($items);
            $priceOne = $rep->price; // grosze

            // Determine display name
            if ($rep->item_id && $rep->item?->product) {
                $name = trim(($rep->item->product->brand?->name ?? '') . ' ' . $rep->item->product->name);
            } elseif ($rep->service_id && $rep->service) {
                $name = $rep->service->name;
            } else {
                $name = 'Pozycja #' . $rep->id;
            }

            // VAT rate: null/0 tax_id → "zw" (margin/komis), otherwise percentage as int
            if (! $rep->tax_id || ! $rep->tax) {
                $vat  = 'zw';
                $name = 'Komis ' . $name;
            } else {
                $vat = (int) $rep->tax->percentage;
            }

            $lineTotal = $priceOne * $quantity;

            // Skip if aggregated line total would be 0 (safety net)
            if ($lineTotal <= 0) {
                continue;
            }

            $pos[] = [
                'a' => $lineTotal / 100, // PLN (złote)
                'n' => $name,
                'v' => $vat,
                'q' => $quantity,
            ];

            $totalPrice += $lineTotal;
        }

        return [
            'bID'     => $this->id,
            'printer' => 0,
            'method'  => 'print',
            'pos'     => $pos,
            'pay'     => [
                'c1' => $totalPrice / 100, // PLN
                'c2' => 0,
                'o'  => 0,
            ],
        ];
    }
}