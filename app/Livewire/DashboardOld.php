<?php

namespace App\Livewire;

use App\Enums\ItemStatus;
use App\Models\Category;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Sell;
use App\Models\Shop;
use App\Models\SoldItem;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Livewire\Component;

class Dashboard extends Component
{
    public ?Shop $shop = null;
    public bool $showTodayStats = true;
    public bool $showMonthStats = false;

    public array $todayCategories = [];
    public array $monthCategories = [];

    private ?array $shopIds = null;

    public function mount(?Shop $shop = null): void
    {
        $this->shop = $shop;
        if ($this->showTodayStats) {
            $this->loadTodayStats();
        }
        if ($this->showMonthStats) {
            $this->loadMonthStats();
        }
    }

    public function loadTodayStats(): void
    {
        $this->todayCategories = $this->getCategoryStats(Carbon::today());
        $this->showTodayStats = true;
    }

    public function loadMonthStats(): void
    {
        $this->monthCategories = $this->getCategoryStats(Carbon::now()->startOfMonth(), now());
        $this->showMonthStats = true;
    }

    private function getShopIds(): array
    {
        if ($this->shopIds === null) {
            $user = auth()->user();
            $this->shopIds = $user->isAdmin()
                ? []
                : $user->shops()->pluck('shops.id')->toArray();
        }
        return $this->shopIds;
    }

    private function scopeShop($query)
    {
        if ($this->shop) {
            return $query->where('parent_shop_id', $this->shop->id);
        }

        $ids = $this->getShopIds();
        if (!empty($ids)) {
            return $query->whereIn('parent_shop_id', $ids);
        }

        return $query;
    }

    private function soldItemsQuery(CarbonInterface $from, ?CarbonInterface $to = null)
    {
        $query = SoldItem::where('sells_items.valid', 1)
            ->join('sells', function ($join) {
                $join->on('sells.id', '=', 'sells_items.sell_id')
                     ->where('sells.valid', 1);

                if ($this->shop) {
                    $join->where('sells.parent_shop_id', $this->shop->id);
                } else {
                    $ids = $this->getShopIds();
                    if (!empty($ids)) {
                        $join->whereIn('sells.parent_shop_id', $ids);
                    }
                }
            });

        if ($to) {
            $query->whereBetween('sells.created_at', [$from, $to]);
        } else {
            $query->whereBetween('sells.created_at', [
                $from->startOfDay(),
                $from->copy()->endOfDay(),
            ]);
        }

        return $query;
    }

    private function getCategoryStats(CarbonInterface $from, ?CarbonInterface $to = null): array
    {
        $baseQuery = fn () => $this->soldItemsQuery($from, $to);

        $deviceCategoryIds    = $this->getDescendantCategoryIds(2);
        $accessoryCategoryIds = $this->getDescendantCategoryIds(3);

        // Devices
        $devicesQuery = fn () => (clone $baseQuery())
            ->where('sells_items.item_id', '>', 0)
            ->whereHas('item.product', fn ($q) => $q->whereIn('parent_category_id', $deviceCategoryIds));

        $devicesRevenue = (clone $devicesQuery())->sum('sells_items.price');
        $devicesCount   = (clone $devicesQuery())->count();

        // Accessories
        $accessoriesQuery = fn () => (clone $baseQuery())
            ->where('sells_items.item_id', '>', 0)
            ->whereHas('item.product', fn ($q) => $q->whereIn('parent_category_id', $accessoryCategoryIds));

        $accessoriesRevenue = (clone $accessoriesQuery())->sum('sells_items.price');
        $accessoriesCount   = (clone $accessoriesQuery())->count();

        // Services
        $servicesQuery = fn () => (clone $baseQuery())->where('sells_items.service_id', '>', 0);

        $servicesRevenue = (clone $servicesQuery())->sum('sells_items.price');
        $servicesCount   = (clone $servicesQuery())->count();

        $stats = [
            'devices'     => ['revenue' => $devicesRevenue,     'count' => $devicesCount],
            'accessories' => ['revenue' => $accessoriesRevenue, 'count' => $accessoriesCount],
            'services'    => ['revenue' => $servicesRevenue,    'count' => $servicesCount],
        ];

        if (auth()->user()->isAdmin()) {
            /**
             * Calculate profit in pure SQL to avoid loading thousands of Eloquent models.
             *
             * Formula:
             *   item profit    = sells_items.price - purchases_items.price * (1 + taxes.percentage / 100)
             *   service profit = sells_items.price - sells_items.internal_cost
             */
            $profitSelect = \DB::raw('
                SUM(
                    sells_items.price - (
                        COALESCE(purchases_items.price, 0)
                        * (1 + COALESCE(taxes.percentage, 0) / 100)
                    )
                )
            ');

            $stats['devices']['profit'] = (int) (clone $devicesQuery())
                ->leftJoin('items', 'items.id', '=', 'sells_items.item_id')
                ->leftJoin('purchases_items', 'purchases_items.item_id', '=', 'items.id')
                ->leftJoin('taxes', 'taxes.id', '=', 'purchases_items.tax_id')
                ->value($profitSelect);

            $stats['accessories']['profit'] = (int) (clone $accessoriesQuery())
                ->leftJoin('items', 'items.id', '=', 'sells_items.item_id')
                ->leftJoin('purchases_items', 'purchases_items.item_id', '=', 'items.id')
                ->leftJoin('taxes', 'taxes.id', '=', 'purchases_items.tax_id')
                ->value($profitSelect);

            $stats['services']['profit'] = (int) (clone $servicesQuery())
                ->value(\DB::raw('SUM(sells_items.price - COALESCE(sells_items.internal_cost, 0))'));

            $stats['totalProfit'] = $stats['devices']['profit']
                + $stats['accessories']['profit']
                + $stats['services']['profit'];
        }

        return $stats;
    }

    private function getDescendantCategoryIds(int $parentId): array
    {
        $ids = [$parentId];
        $children = Category::where('parent_category_id', $parentId)->pluck('id')->toArray();

        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getDescendantCategoryIds($childId));
        }

        return $ids;
    }

    public function render()
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        $itemsInStock = $this->scopeShop(Item::where('status', ItemStatus::Store))->count();

        $todaySells = $this->scopeShop(
            Sell::where('valid', 1)->whereBetween('created_at', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()])
        )->count();

        $todayPurchases = $this->scopeShop(
            Purchase::where('valid', 1)->whereBetween('created_at', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()])
        )->count();        $todayRevenue = $this->soldItemsQuery($today)->sum('sells_items.price');

        $recentSells = $this->scopeShop(
            Sell::with(['shop', 'soldItems' => fn ($q) => $q->where('valid', 1)])
                ->where('valid', 1)
        )->orderByDesc('created_at')->limit(10)->get();

        $recentPurchases = $this->scopeShop(
            Purchase::with(['shop', 'contact', 'purchasedItems'])
                ->where('valid', 1)
        )->orderByDesc('created_at')->limit(10)->get();

        $monthSells = $this->scopeShop(Sell::where('valid', 1)->where('created_at', '>=', $thisMonth))->count();
        $monthRevenue = $this->soldItemsQuery($thisMonth, now())->sum('sells_items.price');

        return view('livewire.dashboard', [
            'itemsInStock' => $itemsInStock,
            'todaySells' => $todaySells,
            'todayRevenue' => $todayRevenue,
            'todayPurchases' => $todayPurchases,
            'monthSells' => $monthSells,
            'monthRevenue' => $monthRevenue,
            'recentSells' => $recentSells,
            'recentPurchases' => $recentPurchases,
            'isAdmin' => auth()->user()->isAdmin(),
        ]);
    }
}