<?php

namespace App\Livewire\Sells;

use App\Enums\SellStatus;
use App\Models\Sell;
use App\Models\Shop;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;

class Show extends Component
{
    public Sell $sell;
    public ?Shop $shop = null;

    private const EAGER_LOADS = [
        'shop',
        'soldItems.item.product.brand',
        'soldItems.item.condition',
        'soldItems.item.purchasedItem.purchase',
        'soldItems.item.purchasedItem.tax',
        'soldItems.tax',
    ];

    public function mount(Sell $sell, ?Shop $shop = null): void
    {   
        // Authorize with the policy; show its message as a toast instead of a 403 wall
        $response = Gate::inspect('view', $sell);

        if ($response->denied()) {
            Flux::toast(variant: 'danger', text: $response->message());
            $this->redirectRoute('dashboard', navigate: true);
            return;
        }
        $this->sell = $sell->load(self::EAGER_LOADS);
        $this->shop = $shop;
    }

    /** @return \Illuminate\Support\Collection<int, \App\Models\SoldItem> */
    #[Computed]
    public function validItems()
    {
        return $this->sell->soldItems->where('valid', true);
    }

    /** Total sell gross price in grosz. */
    #[Computed]
    public function totalSellGross(): int
    {
        return $this->validItems->sum('price');
    }

    /** Total sell net price in grosz. */
    #[Computed]
    public function totalSellNet(): int
    {
        return $this->validItems->sum(fn ($si) => $si->getNetPrice());
    }

    /** Total purchase gross price in grosz. */
    #[Computed]
    public function totalPurchaseGross(): int
    {
        return $this->validItems->sum(function ($si) {
            if ($si->item_id && $si->item?->purchasedItem) {
                return $si->item->purchasedItem->getGrossPrice();
            }
            return $si->internal_cost ?? 0;
        });
    }

    /** Total purchase net price in grosz. */
    #[Computed]
    public function totalPurchaseNet(): int
    {
        return $this->validItems->sum(function ($si) {
            if ($si->item_id && $si->item?->purchasedItem) {
                return $si->item->purchasedItem->price;
            }
            return $si->internal_cost ?? 0;
        });
    }

    /** Total income (sell gross - purchase gross) in grosz. */
    #[Computed]
    public function totalIncome(): int
    {
        return $this->validItems->sum(fn ($si) => $si->getIncome());
    }

    /** JSON payload for the fiscal printer (legacy protocol). */
    #[Computed]
    public function printerPayload(): array
    {
        return $this->sell->buildPrinterPayload();
    }

    /** Whether this sell's receipt was already printed. */
    #[Computed]
    public function isBillPrinted(): bool
    {
        return $this->sell->bill_printed_at !== null;
    }

    #[Computed]
    public function backUrl(): string
    {
        return $this->shop
            ? route('shop.sells.index', $this->shop)
            : route('sells.index');
    }

    /** Mark receipt as printed (called from JS after successful WebSocket send). */
    public function markBillPrinted(): void
    {
        // Guard: total must be > 0 and there must be printable positions
        $payload = $this->sell->buildPrinterPayload();
        if (empty($payload['pos']) || ($payload['pay']['c1'] ?? 0) <= 0) {
            Flux::toast(variant: 'danger', text: 'Nie można wydrukować paragonu — brak pozycji z kwotą > 0 zł.');
            return;
        }

        $this->sell->markBillPrinted();
        $this->sell->refresh();

        // Clear computed properties so the view reflects the new state
        unset($this->isBillPrinted, $this->printerPayload);

        Flux::toast(variant: 'success', text: 'Paragon oznaczony jako wydrukowany.');
    }

    /** Cancel the entire sell – sets valid=0 and returns items to store. */
    public function cancel(): void
    {
        $this->sell->invalidate();
        $this->sell->refresh();
        $this->sell->load(self::EAGER_LOADS);

        // Clear cached computed properties so the view reflects new state
        unset($this->validItems, $this->totalSellGross, $this->totalSellNet,
              $this->totalPurchaseGross, $this->totalPurchaseNet, $this->totalIncome);

        Flux::modal('confirm-cancel-sell')->close();
        Flux::toast(variant: 'success', text: 'Sprzedaż została anulowana.');
    }

    public function render()
    {
        return view('livewire.sells.show', [
            'isAdmin' => auth()->user()->isAdmin(),
        ]);
    }
}