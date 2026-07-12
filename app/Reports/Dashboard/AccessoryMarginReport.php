<?php

namespace App\Reports\Dashboard;

use App\Models\Shop;
use App\Models\SoldItem;
use App\Support\CategoryTree;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Accessory margin % per shop, current vs previous month.
 *
 * NOTE: not wired into the dashboard by default (the original call site was
 * commented out). Kept isolated so it can be enabled without bloating the
 * Livewire component. Delete this file if the feature is dropped.
 */
class AccessoryMarginReport
{
    public function __construct(private readonly CategoryTree $categories) {}

    /**
         * @param  Collection<int, Shop>  $shops
         * @return array<int, array{current: ?int, previous: ?int}>
         */
        public function forShops(Collection $shops): array
        {
            $accessories = $this->categories->descendantIds(config('dashboard.category_roots.accessories'));

            $currentFrom  = CarbonImmutable::now()->startOfMonth();
            $currentTo    = CarbonImmutable::now()->endOfMonth();
            $previousFrom = CarbonImmutable::now()->subMonth()->startOfMonth();
            $previousTo   = CarbonImmutable::now()->subMonth()->endOfMonth();

            $margins = [];

            foreach ($shops as $shop) {
                // Revenue + gross margin in one aggregate row.
                // Gross purchase price = net * (1 + tax%); tax_id 0 (margin scheme) => net only.
                $row = fn ($from, $to) => SoldItem::where('sells_items.valid', 1)
                    ->join('sells as am_sells', fn ($j) => $j
                        ->on('am_sells.id', '=', 'sells_items.sell_id')
                        ->where('am_sells.valid', 1)
                        ->where('am_sells.parent_shop_id', $shop->id))
                    ->join('items as am_items', 'am_items.id', '=', 'sells_items.item_id')
                    ->join('products as am_products', 'am_products.id', '=', 'am_items.product_id')
                    ->leftJoin('purchases_items as am_pi', 'am_pi.item_id', '=', 'am_items.id')
                    ->leftJoin('taxes as am_t', 'am_t.id', '=', 'am_pi.tax_id')
                    ->whereIn('am_products.parent_category_id', $accessories)
                    ->whereBetween('am_sells.created_at', [$from, $to])
                    ->toBase()
                    ->selectRaw('
                        COALESCE(SUM(sells_items.price), 0) as revenue,
                        COALESCE(SUM(sells_items.price - COALESCE(am_pi.price * (1 + COALESCE(am_t.percentage, 0) / 100), 0)), 0) as profit
                    ')
                    ->first();

                $current  = $row($currentFrom, $currentTo);
                $previous = $row($previousFrom, $previousTo);

                $currentRevenue  = (int) ($current->revenue ?? 0);
                $currentMargin   = (int) ($current->profit ?? 0);
                $previousRevenue = (int) ($previous->revenue ?? 0);
                $previousMargin  = (int) ($previous->profit ?? 0);

                $margins[$shop->id] = [
                    'current'  => $currentRevenue  > 0 ? round($currentMargin  / $currentRevenue  * 100, 2) : null,
                    'previous' => $previousRevenue > 0 ? round($previousMargin / $previousRevenue * 100, 2) : null,
                ];
            }

            return $margins;
        }
}