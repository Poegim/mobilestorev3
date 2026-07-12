<?php

namespace App\Reports\Dashboard;

use App\Enums\DashboardPeriod;
use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\Sell;
use App\Models\Shop;
use App\Models\SoldItem;
use App\Models\User;
use App\Support\CategoryTree;
use App\Support\Dashboard\DashboardScope;
use Illuminate\Support\Collection;

class ShopStatsReport
{
    public function __construct(
        private readonly DashboardScope $scope,
        private readonly CategoryTree $categories,
    ) {}

    /**
     * Per-shop stats for the period, ranked by transaction count.
     *
     * @param  array<int, array{current: ?int, previous: ?int}>  $margins  optional accessory margins per shop id
     * @return array<int, array<string, mixed>>
     */
    public function forPeriod(DashboardPeriod $period, ?Shop $shop, User $user, array $margins = []): array
    {
        $stats = $this->rawStats($period, $shop, $user);
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
                'accessories'                => $stat['accessories'],
                'services'                   => $stat['services'],
                'maxTransactions'            => $maxTx,
                'accessoryMarginPctCurrent'  => $margins[$stat['shop']->id]['current']  ?? null,
                'accessoryMarginPctPrevious' => $margins[$stat['shop']->id]['previous'] ?? null,
            ])
            ->all();
    }

    private function rawStats(DashboardPeriod $period, ?Shop $shop, User $user): Collection
    {
        [$from, $to] = $period->range();

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

            return [
                'shop'         => $shop,
                'stock'        => Item::where('parent_shop_id', $shop->id)
                    ->where('status', ItemStatus::Store)
                    ->count(),
                'transactions' => Sell::where('valid', 1)
                    ->where('parent_shop_id', $shop->id)
                    ->whereBetween('created_at', [$from, $to])
                    ->count(),
                'revenue'      => (int) $soldItems()->sum('sells_items.price'),
                'devices'      => $soldItems()->where('sells_items.item_id', '>', 0)
                    ->whereHas('item.product', fn ($q) => $q->whereIn('parent_category_id', $devices))
                    ->count(),
                'accessories'  => $soldItems()->where('sells_items.item_id', '>', 0)
                    ->whereHas('item.product', fn ($q) => $q->whereIn('parent_category_id', $accessories))
                    ->count(),
                'services'     => $soldItems()->where('sells_items.service_id', '>', 0)->count(),
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