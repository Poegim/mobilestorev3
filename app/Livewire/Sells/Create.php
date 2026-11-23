<?php

namespace App\Livewire\Sells;

use App\Models\Shop;
use Livewire\Component;

class Create extends Component
{
    public Shop $shop;
    public ?int $searchId = null;
    public array $items = [];

    public function mount(Shop $shop): void
    {
       $this->shop = $shop;
    }

    public function addItem(int $itemId): void
    {
        if (! in_array($itemId, $this->items, true)) {
            $this->items[] = $itemId;
        }
    }

    public function render()
    {
        return view('livewire.sells.create', [
            'shop' => $this->shop,
            'items' => $this->items,
        ]);
    }
}
