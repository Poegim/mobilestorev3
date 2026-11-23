<?php

namespace App\Reports\Dashboard;

use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\Sell;
use App\Models\Shop;
use App\Models\SoldItem;
use App\Models\User;
use App\Support\CategoryTree;
use App\Support\Dashboard\DashboardScope;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ShopStatsReport
{
    public function __construct(
        private readonly DashboardScope $scope,
        private readonly CategoryTree $categories,
        private readonly bool $withProfit = false,
    ) {}

    public function forPeriod(\App\Enums\DashboardPeriod $period, ?Shop $shop, User $user, array $margins = []): array
    {
        [$from, $to] = $period->range();

        return $this->forRange($from, $to, $shop, $user, $margins);
    }

    public function forRange(CarbonInterface $from, CarbonInterface $to, ?Shop $shop, User $user, array $margins = []): array
    {
        $stats = $this->rawStats($from, $to, $shop, $user);
        $maxTx = $stats->max(fn ($s) => $s['transactions']) ?: 1;

        return $stats
            ->sortByDesc(fn ($s) => $s['transactions'])
            ->values()
            ->map(fn ($stat, $i) => [
                'shopName'                   => $stat['shop']->name,
                'shopColor'                  => $stat['shop']->color ?? '#6366f1',
                'shopId'                     => $stat['shop']->id,
                'rank'                       => $i + 1,
                'stock'                      => $stat['stock'],
                'transactions'               => $stat['transactions'],
                'revenue'                    => $stat['revenue'],
                'devices'                    => $stat['devices'],
                'devicesRevenue'             => $stat['devicesRevenue'],
                'devicesProfit'              => $stat['devicesProfit'],
                'accessories'                => $stat['accessories'],
                'accessoriesRevenue'         => $stat['accessoriesRevenue'],
                'accessoriesProfit'          => $stat['accessoriesProfit'],
                'services'                   => $stat['services'],
                'servicesRevenue'            => $stat['servicesRevenue'],
                'servicesProfit'             => $stat['servicesProfit'],
                'maxTransactions'            => $maxTx,
                'accessoryMarginPctCurrent'  => $margins[$stat['shop']->id]['current']  ?? null,
                'accessoryMarginPctPrevious' => $margins[$stat['shop']->id]['previous'] ?? null,
            ])
            ->all();
    }

    private function rawStats(CarbonInterface $from, CarbonInterface $to, ?Shop $shop, User $user): Collection
    {
        $devices     = $this->categories->descendantIds(config('dashboard.category_roots.devices'));
        $accessories = $this->categories->descendantIds(config('dashboard.category_roots.accessories'));

        return $this->shops($shop, $user)->map(function (Shop $shop) use ($from, $to, $devices, $accessories) {
            // Fresh sold-items query per aggregate — no clone needed.
            $soldItems = fn () => SoldItem::where('sells_items.valid', 1)
                ->join('sells', fn ($j) => $j
                    ->on('sells.id', '=', 'sells_items.sell_id')
                    ->where('sells.valid', 1)
                    ->where('sells.parent_shop_id', $shop->id))
                ->whereBetween('sells.created_at', [$from, $to]);

            // Product category query (devices or accessories).
            $productQuery = fn (array $catIds) => $soldItems()
                ->where('sells_items.item_id', '>', 0)
                ->whereHas('item.product', fn ($q) => $q->whereIn('parent_category_id', $catIds));

            // Product profit via single SQL aggregate (same approach as AccessoryMarginReport).
            $productProfit = fn (array $catIds) => $this->withProfit
                ? (int) ($soldItems()
                    ->join('items as si_items', 'si_items.id', '=', 'sells_items.item_id')
                    ->join('products as si_products', 'si_products.id', '=', 'si_items.product_id')
                    ->leftJoin('purchases_items as si_pi', 'si_pi.item_id', '=', 'si_items.id')
                    ->leftJoin('taxes as si_t', 'si_t.id', '=', 'si_pi.tax_id')
                    ->whereIn('si_products.parent_category_id', $catIds)
                    ->toBase()
                    ->selectRaw('COALESCE(SUM(CAST(sells_items.price AS SIGNED) - CAST(COALESCE(si_pi.price * (1 + COALESCE(si_t.percentage, 0) / 100), 0) AS SIGNED)), 0) as profit')
                    ->value('profit'))
                : null;

            // Service profit: price - internal_cost.
            $serviceProfit = fn () => $this->withProfit
                ? (int) $soldItems()
                    ->where('sells_items.service_id', '>', 0)
                    ->toBase()
                    ->selectRaw('COALESCE(SUM(CAST(sells_items.price AS SIGNED) - CAST(sells_items.internal_cost AS SIGNED)), 0) as profit')                    ->value('profit')
                : null;

            return [
                'shop'               => $shop,
                'stock'              => Item::where('parent_shop_id', $shop->id)
                    ->where('status', ItemStatus::Store)
                    ->count(),
                'transactions'       => Sell::where('valid', 1)
                    ->where('parent_shop_id', $shop->id)
                    ->whereBetween('created_at', [$from, $to])
                    ->count(),
                'revenue'            => (int) $soldItems()->sum('sells_items.price'),
                'devices'            => $productQuery($devices)->count(),
                'devicesRevenue'     => (int) $productQuery($devices)->sum('sells_items.price'),
                'devicesProfit'      => $productProfit($devices),
                'accessories'        => $productQuery($accessories)->count(),
                'accessoriesRevenue' => (int) $productQuery($accessories)->sum('sells_items.price'),
                'accessoriesProfit'  => $productProfit($accessories),
                'services'           => $soldItems()->where('sells_items.service_id', '>', 0)->count(),
                'servicesRevenue'    => (int) $soldItems()->where('sells_items.service_id', '>', 0)->sum('sells_items.price'),
                'servicesProfit'     => $serviceProfit(),
            ];
        });
    }

    private function shops(?Shop $shop, User $user): Collection
    {
        if ($shop) {
            return collect([$shop]);
        }

        $query = $user->isAdmin()
            ? Shop::where('archive', false)
            : $user->shops()->where('archive', false);
            

        return $query->orderBy('order')->orderBy('id')->get();
    }
}