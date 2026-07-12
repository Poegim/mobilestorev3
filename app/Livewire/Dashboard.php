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
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    public ?Shop $shop = null;

    // region Lazy-load flags

    /** Revenue breakdown loaded (admin only) */
    public bool $revenueLoaded = false;

    /** Per-shop stats loaded */
    public bool $shopStatsLoaded = false;

    /** Top products & trending loaded */
    public bool $topProductsLoaded = false;

    // endregion

    // region Lazy-loaded data (persisted in component state)

    public int $todayRevenue = 0;
    public int $todaySells = 0;
    public int $monthRevenue = 0;
    public int $monthSells = 0;

    public array $todayCategories = [];
    public array $monthCategories = [];

    public array $shopStats = [];

    // endregion

    private ?array $shopIds = null;
    private ?array $descendantCache = [];

    public string $period = 'today';

    #[Computed]
    public function periodLabel(): string
    {
        return match($this->period) {
            'yesterday'   => 'Wczoraj',
            'week'        => 'Ten tydzień',
            'month'       => 'Ten miesiąc',
            'last30'      => 'Ostatnie 30 dni',
            'last60'      => 'Ostatnie 60 dni',
            'last90'      => 'Ostatnie 90 dni',
            'quarter'     => 'Ten kwartał',
            'lastquarter' => 'Poprzedni kwartał',
            'year'        => 'Ten rok',
            'lastyear'    => 'Poprzedni rok',
            default       => 'Dziś',
        };
    }

    public function mount(?Shop $shop = null): void
    {
        $this->shop = $shop;

        $today = Carbon::today();

        $this->todaySells = $this->scopeShop(
            Sell::where('valid', 1)
                ->whereBetween('created_at', [$today->startOfDay(), $today->copy()->endOfDay()])
        )->count();

        $this->todayRevenue = 0;
        $this->monthRevenue = 0;
        $this->monthSells   = 0;

        // Auto-load default modules on first render
        if (auth()->user()->isAdmin()) {
            $this->loadRevenueStats();
        }
        $this->loadShopStats();
    }

    // region Lazy-load actions

    /**
     * Load full revenue breakdown with category stats (admin).
     * Called on button click — heaviest query set.
     */
    public function loadRevenueStats(): void
    {
        [$from, $to] = $this->getPeriodRange();

        $this->todayRevenue = $this->soldItemsQuery($from, $to)->sum('sells_items.price');
        $this->todayCategories = $this->getCategoryStats($from, $to);

        $this->todaySells = $this->scopeShop(
            Sell::where('valid', 1)->whereBetween('created_at', [$from, $to])
        )->count();

        // Month summary stays as reference point — always current month
        $thisMonth = Carbon::now()->startOfMonth();
        $this->monthRevenue = $this->soldItemsQuery($thisMonth, now())->sum('sells_items.price');
        $this->monthSells = $this->scopeShop(
            Sell::where('valid', 1)->where('created_at', '>=', $thisMonth)
        )->count();
        $this->monthCategories = $this->getCategoryStats($thisMonth, now());

        $this->revenueLoaded = true;
    }

    /**
     * Load per-shop quantity stats.
     * Moderate cost — loops over shops with COUNT queries.
     */
    public function loadShopStats(): void
    {
        $stats = $this->getPerShopCountStats();

        // Compute maxTransactions for progress bar scaling
        $maxTx = $stats->max(fn ($s) => $s['today']['transactions']) ?: 1;

        // Flatten to match x-dashboard-shop-card props and sort by today transactions desc
        $this->shopStats = $stats
            ->sortByDesc(fn ($s) => $s['today']['transactions'])
            ->values()
            ->map(fn ($stat, $i) => [
                'shopName'        => $stat['shop']->name,
                'shopColor'       => $stat['shop']->color ?? '#6366f1',
                'rank'            => $i + 1,
                'stock'           => $stat['stock'],
                'transactions'    => $stat['today']['transactions'],
                'revenue'         => $stat['today']['revenue'],
                'devices'         => $stat['today']['devices'],
                'accessories'     => $stat['today']['accessories'],
                'services'        => $stat['today']['services'],
                'maxTransactions' => $maxTx,
            ])
            ->toArray();

        $this->shopStatsLoaded = true;
    }

    /**
     * Reveal top products section.
     * The actual queries run inside the TopProducts child component on mount.
     */
    public function loadTopProducts(): void
    {
        $this->topProductsLoaded = true;
    }

    // endregion

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
                ? Shop::where('archive', false)->orderBy('order')->get()
                : $user->shops()->where('archive', false)->orderBy('order')->get());

        [$from, $to] = $this->getPeriodRange();
        $fromImmutable = $from->toImmutable();
        $toImmutable   = $to->toImmutable();

        $deviceCategoryIds    = $this->getDescendantCategoryIds(2);
        $accessoryCategoryIds = $this->getDescendantCategoryIds(3);

        return $shops->map(function (Shop $shop) use ($fromImmutable, $toImmutable, $deviceCategoryIds, $accessoryCategoryIds) {
            $stock = Item::where('parent_shop_id', $shop->id)
                ->where('status', ItemStatus::Store)
                ->count();

            $soldItems = SoldItem::where('sells_items.valid', 1)
                ->join('sells', fn ($j) => $j
                    ->on('sells.id', '=', 'sells_items.sell_id')
                    ->where('sells.valid', 1)
                    ->where('sells.parent_shop_id', $shop->id)
                )
                ->whereBetween('sells.created_at', [$fromImmutable, $toImmutable]);

            $transactions = Sell::where('valid', 1)
                ->where('parent_shop_id', $shop->id)
                ->whereBetween('created_at', [$fromImmutable, $toImmutable])
                ->count();

            $total       = (clone $soldItems)->count();
            $devices     = (clone $soldItems)->where('sells_items.item_id', '>', 0)->whereHas('item.product', fn ($q) => $q->whereIn('parent_category_id', $deviceCategoryIds))->count();
            $accessories = (clone $soldItems)->where('sells_items.item_id', '>', 0)->whereHas('item.product', fn ($q) => $q->whereIn('parent_category_id', $accessoryCategoryIds))->count();
            $services    = (clone $soldItems)->where('sells_items.service_id', '>', 0)->count();
            $revenue     = (clone $soldItems)->sum('sells_items.price');

            return [
                'shop'  => $shop,
                'stock' => $stock,
                'today' => compact('transactions', 'revenue', 'total', 'devices', 'accessories', 'services'),
                'month' => [], // unused — period switcher covers this
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

    public function updatedPeriod(): void
    {
        $wasRevenueLoaded = $this->revenueLoaded;
        $wasShopLoaded    = $this->shopStatsLoaded;

        $this->revenueLoaded   = false;
        $this->shopStatsLoaded = false;

        if ($wasRevenueLoaded)  $this->loadRevenueStats();
        if ($wasShopLoaded)     $this->loadShopStats();
    }

    private function getPeriodRange(): array
    {
        return match ($this->period) {
            'yesterday'   => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()],
            'week'        => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'month'       => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'last30'      => [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()->endOfDay()],
            'last60'      => [Carbon::now()->subDays(60)->startOfDay(), Carbon::now()->endOfDay()],
            'last90'      => [Carbon::now()->subDays(90)->startOfDay(), Carbon::now()->endOfDay()],
            'quarter'     => [Carbon::now()->startOfQuarter(), Carbon::now()->endOfQuarter()],
            'lastquarter' => [Carbon::now()->subQuarter()->startOfQuarter(), Carbon::now()->subQuarter()->endOfQuarter()],
            'year'        => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            'lastyear'    => [Carbon::now()->subYear()->startOfYear(), Carbon::now()->subYear()->endOfYear()],
            default       => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
        };
    }

    public function render()
    {
        $isAdmin = auth()->user()->isAdmin();

        // Instant stats — cheap COUNT queries only
        $itemsInStock = $this->scopeShop(Item::where('status', ItemStatus::Store))->count();

        $todayPurchases = $this->scopeShop(
            Purchase::where('valid', 1)
                ->whereBetween('created_at', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()])
        )->count();

        return view('livewire.dashboard', [
            'itemsInStock' => $itemsInStock,
            'todayPurchases' => $todayPurchases,
            'isAdmin' => $isAdmin,
            'period' => $this->period,
        ]);
    }
}