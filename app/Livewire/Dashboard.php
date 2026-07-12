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
use Illuminate\Support\Facades\DB;
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
    public int $todaySells   = 0;
    public int $monthRevenue = 0;
    public int $monthSells   = 0;

    public array $todayCategories = [];
    public array $monthCategories = [];
    public array $shopStats        = [];

    /**
     * Accessory margin % per shop — loaded once, independent of period switcher.
     * Keyed by shop ID: ['current' => int|null, 'previous' => int|null]
     */
    public array $shopAccessoryMargins = [];

    // endregion

    private ?array $shopIds       = null;
    private array  $descendantCache = [];

    public string $period = 'today';

    #[Computed]
    public function periodLabel(): string
    {
        return match ($this->period) {
            'yesterday'   => 'Wczoraj',
            'week'        => 'Ten tydzień',
            'month'       => 'Ten miesiąc',
            'last30'      => 'Ostatnie 30 dni',
            'last60'      => 'Ostatnie 60 dni',
            'last90'      => 'Ostatnie 90 dni',
            'quarter'     => 'Ten kwartał',
            'lastquarter' => 'Poprzedni kwartał',
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

    // Auto-load default modules on first render — today only, fast enough
    if (auth()->user()->isAdmin()) {
        $this->loadRevenueStats();
    }
    $this->loadShopStats();
}

    // region Lazy-load actions

    /**
     * Load full revenue breakdown with category stats (admin).
     */
    public function loadRevenueStats(): void
    {
        [$from, $to] = $this->getPeriodRange();

        $this->todayRevenue    = $this->soldItemsQuery($from, $to)->sum('sells_items.price');
        $this->todayCategories = $this->getCategoryStats($from, $to);

        $this->todaySells = $this->scopeShop(
            Sell::where('valid', 1)->whereBetween('created_at', [$from, $to])
        )->count();

        // Month summary — always current month regardless of period
        $thisMonth          = Carbon::now()->startOfMonth();
        $this->monthRevenue = $this->soldItemsQuery($thisMonth, now())->sum('sells_items.price');
        $this->monthSells   = $this->scopeShop(
            Sell::where('valid', 1)->where('created_at', '>=', $thisMonth)
        )->count();
        $this->monthCategories = $this->getCategoryStats($thisMonth, now());

        $this->revenueLoaded = true;
    }

    /**
     * Load per-shop quantity stats for the selected period.
     * Accessory margins are loaded once and cached in $shopAccessoryMargins.
     */
    public function loadShopStats(): void
    {
        $stats = $this->getPerShopCountStats();

        // Load accessory margins only once — they never depend on the period
        // if (empty($this->shopAccessoryMargins)) {
        //     $this->shopAccessoryMargins = $this->getShopAccessoryMargins($stats);
        // }

        $maxTx = $stats->max(fn ($s) => $s['today']['transactions']) ?: 1;

        $this->shopStats = $stats
            ->sortByDesc(fn ($s) => $s['today']['transactions'])
            ->values()
            ->map(fn ($stat, $i) => [
                'shopName'                   => $stat['shop']->name,
                'shopColor'                  => $stat['shop']->color ?? '#6366f1',
                'shopId'                     => $stat['shop']->id,
                'rank'                       => $i + 1,
                'stock'                      => $stat['stock'],
                'transactions'               => $stat['today']['transactions'],
                'revenue'                    => $stat['today']['revenue'],
                'devices'                    => $stat['today']['devices'],
                'accessories'                => $stat['today']['accessories'],
                'services'                   => $stat['today']['services'],
                'maxTransactions'            => $maxTx,
                'accessoryMarginPctCurrent'  => $this->shopAccessoryMargins[$stat['shop']->id]['current']  ?? null,
                'accessoryMarginPctPrevious' => $this->shopAccessoryMargins[$stat['shop']->id]['previous'] ?? null,
            ])
            ->toArray();

        $this->shopStatsLoaded = true;
    }

    /**
     * Reveal top products section.
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
            $user            = auth()->user();
            $this->shopIds   = $user->isAdmin()
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
        $deviceCategoryIds    = $this->getDescendantCategoryIds(2);
        $accessoryCategoryIds = $this->getDescendantCategoryIds(3);

        // Fast counts/sums via JOIN instead of whereHas
        $base = fn () => $this->soldItemsQuery($from, $to)
            ->leftJoin('items', 'items.id', '=', 'sells_items.item_id')
            ->leftJoin('products', 'products.id', '=', 'items.product_id');

        $devicesRevenue = (clone $base())
            ->whereIn('products.parent_category_id', $deviceCategoryIds)
            ->sum('sells_items.price');

        $devicesCount = (clone $base())
            ->whereIn('products.parent_category_id', $deviceCategoryIds)
            ->count();

        $accessoriesRevenue = (clone $base())
            ->whereIn('products.parent_category_id', $accessoryCategoryIds)
            ->sum('sells_items.price');

        $accessoriesCount = (clone $base())
            ->whereIn('products.parent_category_id', $accessoryCategoryIds)
            ->count();

        $servicesRevenue = $this->soldItemsQuery($from, $to)
            ->where('sells_items.service_id', '>', 0)
            ->sum('sells_items.price');

        $servicesCount = $this->soldItemsQuery($from, $to)
            ->where('sells_items.service_id', '>', 0)
            ->count();

        $stats = [
            'devices'     => ['revenue' => $devicesRevenue,     'count' => $devicesCount],
            'accessories' => ['revenue' => $accessoriesRevenue, 'count' => $accessoriesCount],
            'services'    => ['revenue' => $servicesRevenue,    'count' => $servicesCount],
        ];

        if (auth()->user()->isAdmin()) {
            // Profit via chunk — avoids loading all models at once
            $devicesProfit     = 0;
            $accessoriesProfit = 0;
            $servicesProfit    = 0;

            // Devices profit
            (clone $base())
                ->whereIn('products.parent_category_id', $deviceCategoryIds)
                ->with(['item.purchasedItem.tax'])
                ->select('sells_items.*')
                ->chunk(500, function ($items) use (&$devicesProfit) {
                    $devicesProfit += $items->sum(fn ($si) => $si->getIncome());
                });

            // Accessories profit
            (clone $base())
                ->whereIn('products.parent_category_id', $accessoryCategoryIds)
                ->with(['item.purchasedItem.tax'])
                ->select('sells_items.*')
                ->chunk(500, function ($items) use (&$accessoriesProfit) {
                    $accessoriesProfit += $items->sum(fn ($si) => $si->getIncome());
                });

            // Services profit — lightweight, no joins needed
            $this->soldItemsQuery($from, $to)
                ->where('sells_items.service_id', '>', 0)
                ->select('sells_items.price', 'sells_items.internal_cost')
                ->chunk(500, function ($items) use (&$servicesProfit) {
                    $servicesProfit += $items->sum(fn ($si) => $si->price - $si->internal_cost);
                });

            $stats['devices']['profit']     = $devicesProfit;
            $stats['accessories']['profit'] = $accessoriesProfit;
            $stats['services']['profit']    = $servicesProfit;
            $stats['totalProfit']           = $devicesProfit + $accessoriesProfit + $servicesProfit;
        }

        return $stats;
    }

    // endregion

    // region Per-shop count stats

    private function getPerShopCountStats(): Collection
    {
        $user  = auth()->user();
        $shops = $this->shop
            ? collect([$this->shop])
            : ($user->isAdmin()
                ? Shop::where('archive', false)->orderBy('order')->orderBy('id')->get()
                : $user->shops()->where('archive', false)->orderBy('order')->orderBy('id')->get());

        [$from, $to]   = $this->getPeriodRange();
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
            $devices     = (clone $soldItems)->where('sells_items.item_id', '>', 0)
                ->whereHas('item.product', fn ($q) => $q->whereIn('parent_category_id', $deviceCategoryIds))
                ->count();
            $accessories = (clone $soldItems)->where('sells_items.item_id', '>', 0)
                ->whereHas('item.product', fn ($q) => $q->whereIn('parent_category_id', $accessoryCategoryIds))
                ->count();
            $services    = (clone $soldItems)->where('sells_items.service_id', '>', 0)->count();
            $revenue     = (clone $soldItems)->sum('sells_items.price');

            return [
                'shop'  => $shop,
                'stock' => $stock,
                'today' => compact('transactions', 'revenue', 'total', 'devices', 'accessories', 'services'),
            ];
        });
    }

    /**
     * Calculate accessory margin % for current and previous month per shop.
     * Called once and cached in $shopAccessoryMargins — independent of period.
     */
    private function getShopAccessoryMargins(Collection $shops): array
    {
        $accessoryCategoryIds = $this->getDescendantCategoryIds(3);

        $currentMonthStart  = Carbon::now()->startOfMonth()->toImmutable();
        $currentMonthEnd    = Carbon::now()->endOfMonth()->toImmutable();
        $previousMonthStart = Carbon::now()->subMonth()->startOfMonth()->toImmutable();
        $previousMonthEnd   = Carbon::now()->subMonth()->endOfMonth()->toImmutable();

        $margins = [];

        foreach ($shops as $stat) {
            $shop = $stat['shop'];

            $base = fn ($from, $to) => SoldItem::where('sells_items.valid', 1)
                ->join('sells as am_sells', fn ($j) => $j
                    ->on('am_sells.id', '=', 'sells_items.sell_id')
                    ->where('am_sells.valid', 1)
                    ->where('am_sells.parent_shop_id', $shop->id)
                )
                ->join('items as am_items', 'am_items.id', '=', 'sells_items.item_id')
                ->join('products as am_products', 'am_products.id', '=', 'am_items.product_id')
                ->leftJoin('purchases_items as am_pi', 'am_pi.item_id', '=', 'am_items.id')
                ->leftJoin('taxes as am_t', 'am_t.id', '=', 'am_pi.tax_id')
                ->whereIn('am_products.parent_category_id', $accessoryCategoryIds)
                ->whereBetween('am_sells.created_at', [$from, $to]);

            $profitSql  = DB::raw('SUM(sells_items.price - COALESCE(am_pi.price * (1 + COALESCE(am_t.percentage, 0) / 100), 0))');
            $revenueSql = DB::raw('SUM(sells_items.price)');

            $currentRevenue  = (int) (clone $base($currentMonthStart, $currentMonthEnd))->value($revenueSql);
            $currentMargin   = (int) (clone $base($currentMonthStart, $currentMonthEnd))->value($profitSql);
            $previousRevenue = (int) (clone $base($previousMonthStart, $previousMonthEnd))->value($revenueSql);
            $previousMargin  = (int) (clone $base($previousMonthStart, $previousMonthEnd))->value($profitSql);

            $margins[$shop->id] = [
                'current'  => $currentRevenue  > 0 ? round($currentMargin  / $currentRevenue  * 100) : null,
                'previous' => $previousRevenue > 0 ? round($previousMargin / $previousRevenue * 100) : null,
            ];
        }

        return $margins;
    }

    // endregion

    private function getDescendantCategoryIds(int $parentId): array
    {
        if (isset($this->descendantCache[$parentId])) {
            return $this->descendantCache[$parentId];
        }

        $ids      = [$parentId];
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

        if ($wasRevenueLoaded) $this->loadRevenueStats();
        if ($wasShopLoaded)    $this->loadShopStats();
    }

    private function getPeriodRange(): array
    {
        return match ($this->period) {
            'yesterday'   => [Carbon::yesterday()->startOfDay(),                        Carbon::yesterday()->endOfDay()],
            'week'        => [Carbon::now()->startOfWeek(),                             Carbon::now()->endOfWeek()],
            'month'       => [Carbon::now()->startOfMonth(),                            Carbon::now()->endOfMonth()],
            'last30'      => [Carbon::now()->subDays(30)->startOfDay(),                 Carbon::now()->endOfDay()],
            'last60'      => [Carbon::now()->subDays(60)->startOfDay(),                 Carbon::now()->endOfDay()],
            'last90'      => [Carbon::now()->subDays(90)->startOfDay(),                 Carbon::now()->endOfDay()],
            'quarter'     => [Carbon::now()->startOfQuarter(),                          Carbon::now()->endOfQuarter()],
            'lastquarter' => [Carbon::now()->subQuarter()->startOfQuarter(),            Carbon::now()->subQuarter()->endOfQuarter()],
            'year'        => [Carbon::now()->startOfYear(),                             Carbon::now()->endOfYear()],
            'lastyear'    => [Carbon::now()->subYear()->startOfYear(),                  Carbon::now()->subYear()->endOfYear()],
            default       => [Carbon::today()->startOfDay(),                            Carbon::today()->endOfDay()],
        };
    }

    public function render()
    {
        $isAdmin = auth()->user()->isAdmin();

        $itemsInStock = $this->scopeShop(Item::where('status', ItemStatus::Store))->count();

        $todayPurchases = $this->scopeShop(
            Purchase::where('valid', 1)
                ->whereBetween('created_at', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()])
        )->count();

        return view('livewire.dashboard', [
            'itemsInStock'   => $itemsInStock,
            'todayPurchases' => $todayPurchases,
            'isAdmin'        => $isAdmin,
            'period'         => $this->period,
        ]);
    }
}