<?php

// sail artisan make:livewire Items/Show

namespace App\Livewire\Items;

use App\Models\Item;
use App\Models\Shop;
use Livewire\Component;

class Show extends Component
{
    public Item $item;
    public ?Shop $shop = null;

    public function mount(Item $item, ?Shop $shop = null): void
    {
        $this->shop = $shop;

        $this->item = $item->load([
            'product.brand',
            'product.category',
            'variant.devices.brand',
            'condition',
            'color',
            'shop',
            'purchasedItem.purchase.contact',
            'purchasedItem.purchase.shop',
            'purchasedItem.tax',
            'soldItem.sell',
            'soldItems.sell',
            'soldItems.tax',
            'transfers.sourceShop',
            'transfers.targetShop',
        ]);
    }

    public function render()
    {
        return view('livewire.items.show');
    }
}