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
use Illuminate\Support\Collection;
use Livewire\Component;

class Dashboard extends Component
{
    public ?Shop $shop = null;

    public array $todayCategories = [];
    public array $monthCategories = [];

    private ?array $shopIds = null;
    private ?array $descendantCache = [];

    #[Computed]
    public function shopRanking(): array
    {
        return $this->getUserShops()
            ->map(fn (Shop $shop) => [
                'name'         => $shop->name,
                'color'        => $shop->color,
                'revenue'      => $this->getRevenueForShop($shop, $this->leaderboardPeriod),
                'transactions' => $this->getTransactionsForShop($shop, $this->leaderboardPeriod),
            ])
            ->sortByDesc('revenue')
            ->values()
            ->all();
    }

    public function mount(?Shop $shop = null): void
    {
        $this->shop = $shop;
        $this->todayCategories = $this->getCategoryStats(Carbon::today());
        $this->monthCategories = $this->getCategoryStats(Carbon::now()->startOfMonth(), now());
    }

    // region Scoping helpers

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

    // endregion

    // region Sold items query builder

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

    // endregion

    // region Category stats (admin — with revenue & profit)

    private function getCategoryStats(CarbonInterface $from, ?CarbonInterface $to = null): array
    {
        $baseQuery = fn () => $this->soldItemsQuery($from, $to);

        $deviceCategoryIds = $this->getDescendantCategoryIds(2);
        $accessoryCategoryIds = $this->getDescendantCategoryIds(3);

        // Devices
        $devicesQuery = fn () => (clone $baseQuery())
            ->where('sells_items.item_id', '>', 0)
            ->whereHas('item.product', fn ($q) => $q->whereIn('parent_category_id', $deviceCategoryIds));

        $devicesRevenue = (clone $devicesQuery())->sum('sells_items.price');
        $devicesCount = (clone $devicesQuery())->count();

        // Accessories
        $accessoriesQuery = fn () => (clone $baseQuery())
            ->where('sells_items.item_id', '>', 0)
            ->whereHas('item.product', fn ($q) => $q->whereIn('parent_category_id', $accessoryCategoryIds));

        $accessoriesRevenue = (clone $accessoriesQuery())->sum('sells_items.price');
        $accessoriesCount = (clone $accessoriesQuery())->count();

        // Services
        $servicesQuery = fn () => (clone $baseQuery())->where('sells_items.service_id', '>', 0);

        $servicesRevenue = (clone $servicesQuery())->sum('sells_items.price');
        $servicesCount = (clone $servicesQuery())->count();

        $stats = [
            'devices' => ['revenue' => $devicesRevenue, 'count' => $devicesCount],
            'accessories' => ['revenue' => $accessoriesRevenue, 'count' => $accessoriesCount],
            'services' => ['revenue' => $servicesRevenue, 'count' => $servicesCount],
        ];

        if (auth()->user()->isAdmin()) {
            $stats['devices']['profit'] = (clone $devicesQuery())
                ->with(['item.purchasedItem.tax'])
                ->get()
                ->sum(fn ($si) => $si->getIncome());

            $stats['accessories']['profit'] = (clone $accessoriesQuery())
                ->with(['item.purchasedItem.tax'])
                ->get()
                ->sum(fn ($si) => $si->getIncome());

            $stats['services']['profit'] = (clone $servicesQuery())
                ->get()
                ->sum(fn ($si) => $si->price - $si->internal_cost);

            $stats['totalProfit'] = $stats['devices']['profit']
                + $stats['accessories']['profit']
                + $stats['services']['profit'];
        }

        return $stats;
    }

    // endregion

    // region Per-shop count stats (everyone)

    /**
     * Quantity-only stats per shop — visible to all users.
     * Returns a collection of shops with today/month transaction and category counts.
     */
    private function getPerShopCountStats(): Collection
    {
        $user = auth()->user();
        $shops = $this->shop
            ? collect([$this->shop])
            : ($user->isAdmin()
                ? Shop::where('archive', false)->where('id', '!=', 8)->orderBy('id')->where('id', '!=', 8)->get()
                : $user->shops()->where('archive', false)->where('shops.id', '!=', 8)->orderBy('shops.id')->get());

        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        $deviceCategoryIds = $this->getDescendantCategoryIds(2);
        $accessoryCategoryIds = $this->getDescendantCategoryIds(3);

        return $shops->map(function (Shop $shop) use ($today, $thisMonth, $deviceCategoryIds, $accessoryCategoryIds) {
            // Today's transaction count
            $todayTransactions = Sell::where('valid', 1)
                ->where('parent_shop_id', $shop->id)
                ->whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
                ->count();

            // Today's sold items by category
            $todaySoldItems = SoldItem::where('sells_items.valid', 1)
                ->join('sells', fn ($j) => $j
                    ->on('sells.id', '=', 'sells_items.sell_id')
                    ->where('sells.valid', 1)
                    ->where('sells.parent_shop_id', $shop->id)
                )
                ->whereBetween('sells.created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()]);

            $todayDevices = (clone $todaySoldItems)
                ->where('sells_items.item_id', '>', 0)
                ->whereHas('item.product', fn ($q) => $q->whereIn('parent_category_id', $deviceCategoryIds))
                ->count();

            $todayAccessories = (clone $todaySoldItems)
                ->where('sells_items.item_id', '>', 0)
                ->whereHas('item.product', fn ($q) => $q->whereIn('parent_category_id', $accessoryCategoryIds))
                ->count();

            $todayServices = (clone $todaySoldItems)
                ->where('sells_items.service_id', '>', 0)
                ->count();

            $todayTotal = $todayDevices + $todayAccessories + $todayServices;

            // Month transaction count
            $monthTransactions = Sell::where('valid', 1)
                ->where('parent_shop_id', $shop->id)
                ->where('created_at', '>=', $thisMonth)
                ->count();

            // Month sold items by category
            $monthSoldItems = SoldItem::where('sells_items.valid', 1)
                ->join('sells', fn ($j) => $j
                    ->on('sells.id', '=', 'sells_items.sell_id')
                    ->where('sells.valid', 1)
                    ->where('sells.parent_shop_id', $shop->id)
                )
                ->where('sells.created_at', '>=', $thisMonth);

            $monthDevices = (clone $monthSoldItems)
                ->where('sells_items.item_id', '>', 0)
                ->whereHas('item.product', fn ($q) => $q->whereIn('parent_category_id', $deviceCategoryIds))
                ->count();

            $monthAccessories = (clone $monthSoldItems)
                ->where('sells_items.item_id', '>', 0)
                ->whereHas('item.product', fn ($q) => $q->whereIn('parent_category_id', $accessoryCategoryIds))
                ->count();

            $monthServices = (clone $monthSoldItems)
                ->where('sells_items.service_id', '>', 0)
                ->count();

            $monthTotal = $monthDevices + $monthAccessories + $monthServices;

            // Stock count
            $stockCount = Item::where('status', ItemStatus::Store)
                ->where('parent_shop_id', $shop->id)
                ->count();

            return [
                'shop' => $shop,
                'stock' => $stockCount,
                'today' => [
                    'transactions' => $todayTransactions,
                    'items' => $todayTotal,
                    'devices' => $todayDevices,
                    'accessories' => $todayAccessories,
                    'services' => $todayServices,
                ],
                'month' => [
                    'transactions' => $monthTransactions,
                    'items' => $monthTotal,
                    'devices' => $monthDevices,
                    'accessories' => $monthAccessories,
                    'services' => $monthServices,
                ],
            ];
        });
    }

    // endregion

    private function getDescendantCategoryIds(int $parentId): array
    {
        if (isset($this->descendantCache[$parentId])) {
            return $this->descendantCache[$parentId];
        }

        $ids = [$parentId];
        $children = Category::where('parent_category_id', $parentId)->pluck('id')->toArray();

        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getDescendantCategoryIds($childId));
        }

        return $this->descendantCache[$parentId] = $ids;
    }


    public string $leaderboardPeriod = 'month';


    public function render()
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $isAdmin = auth()->user()->isAdmin();

        $itemsInStock = $this->scopeShop(Item::where('status', ItemStatus::Store))->count();

        $todaySells = $this->scopeShop(
            Sell::where('valid', 1)->whereBetween('created_at', [$today->startOfDay(), $today->copy()->endOfDay()])
        )->count();

        $todayPurchases = $this->scopeShop(
            Purchase::where('valid', 1)->whereBetween('created_at', [$today->startOfDay(), $today->copy()->endOfDay()])
        )->count();

        $todayRevenue = $isAdmin
            ? $this->soldItemsQuery($today)->sum('sells_items.price')
            : 0;

        $monthSells = $this->scopeShop(
            Sell::where('valid', 1)->where('created_at', '>=', $thisMonth)
        )->count();

        $monthRevenue = $isAdmin
            ? $this->soldItemsQuery($thisMonth, now())->sum('sells_items.price')
            : 0;

        $shopStats = $this->getPerShopCountStats();

        return view('livewire.dashboard', [
            'itemsInStock' => $itemsInStock,
            'todaySells' => $todaySells,
            'todayRevenue' => $todayRevenue,
            'todayPurchases' => $todayPurchases,
            'monthSells' => $monthSells,
            'monthRevenue' => $monthRevenue,
            'shopStats' => $shopStats,
            'isAdmin' => $isAdmin,
        ]);
    }
}