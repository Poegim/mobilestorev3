<?php

namespace App\Livewire\Purchases;

use App\Models\Purchase;
use App\Models\Shop;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    public Purchase $purchase;

    public ?Shop $shop = null;

    private const EAGER_LOADS = [
        'shop',
        'contact',
        'creator',
        'purchasedItems.tax',
        'purchasedItems.item.product.brand',
        'purchasedItems.item.variant.devices',
        'purchasedItems.item.condition',
        'purchasedItems.item.color',
    ];

    public function mount(Purchase $purchase, ?Shop $shop = null): void
    {
        $this->denyIf(
            $purchase->is_draft,
            'Ten zakup jest jeszcze szkicem i nie może być wyświetlony.'
        );

        $user = auth()->user();
        $this->denyUnless(
            $user->isAdmin() || $user->shops()->whereKey($purchase->parent_shop_id)->exists(),
            'Nie masz dostępu do zakupów tego sklepu.'
        );

        $this->purchase = $purchase->load(self::EAGER_LOADS);
        $this->shop = $shop;
    }

    /**
     * Purchased items merged into table lines: identical accessories bought in bulk
     * become one line with a quantity; items with an IMEI are never merged.
     *
     * @return \Illuminate\Support\Collection<int, array{pi: \App\Models\PurchasedItem, qty: int}>
     */
    #[Computed]
    public function lines()
    {
        return $this->purchase->purchasedItems
            ->groupBy(fn ($pi) => implode('|', [
                $pi->item?->product_id,
                $pi->item?->feature_condition_id,
                $pi->item?->feature_color_id,
                $pi->item?->feature_imei ?: 'unique-' . $pi->id,
                $pi->item?->status?->value,
                $pi->tax_id,
                $pi->price,
            ]))
            ->map(fn ($group) => ['pi' => $group->first(), 'qty' => $group->count()])
            ->values();
    }

    #[Computed]
    public function backUrl(): string
    {
        return $this->shop
            ? route('shop.purchases.index', $this->shop)
            : route('purchases.index');
    }

    public function render()
    {
        return view('livewire.purchases.show')
            ->title("Zakup #{$this->purchase->id}");
    }
    
}