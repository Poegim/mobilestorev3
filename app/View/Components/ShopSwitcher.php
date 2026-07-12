<?php

namespace App\View\Components;

use App\Models\Shop;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;

class ShopSwitcher extends Component
{
    public ?Shop $currentShop;
    public Collection $shops;

    public function __construct()
    {
        $shopParam = request()->route('shop');

        if ($shopParam instanceof Shop) {
            $this->currentShop = $shopParam;
        } elseif (is_numeric($shopParam)) {
            $this->currentShop = Shop::find($shopParam);
        } else {
            $this->currentShop = null;
        }


        $user = auth()->user();
        $this->shops = $user->isAdmin()
            ? Shop::where('archive', false)->orderBy('order')->get()
            : $user->shops()->where('archive', false)->orderBy('order')->orderBy('id')->get();
    }

    public function render(): View
    {
        return view('components.shop-switcher');
    }
}