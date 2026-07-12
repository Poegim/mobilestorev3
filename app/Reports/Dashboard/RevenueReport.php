<?php

namespace App\Reports\Dashboard;

use App\Enums\DashboardPeriod;
use App\Models\Sell;
use App\Models\SoldItem;
use App\Support\CategoryTree;
use App\Support\Dashboard\DashboardScope;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class RevenueReport
{
    public function __construct(
        private readonly DashboardScope $scope,
        private readonly CategoryTree $categories,
        private readonly bool $withProfit,
    ) {}

    /** @return array{revenue: int, sells: int, categories: array} */
    public function forPeriod(DashboardPeriod $period): array
    {
        [$from, $to] = $period->range();

        return [
            'revenue'    => (int) $this->soldItems($from, $to)->sum('sells_items.price'),
            'sells'      => $this->scope->apply(
                Sell::where('valid', 1)->whereBetween('created_at', [$from, $to])
            )->count(),
            'categories' => $this->categoryStats($from, $to),
        ];
    }

    /**
     * Current-month summary — always this month, independent of the period switcher.
     *
     * @return array{revenue: int, sells: int, categories: array}
     */
    public function currentMonth(): array
    {
        $from = CarbonImmutable::now()->startOfMonth();
        $to   = CarbonImmutable::now();

        return [
            'revenue'    => (int) $this->soldItems($from, $to)->sum('sells_items.price'),
            'sells'      => $this->scope->apply(
                Sell::where('valid', 1)->where('created_at', '>=', $from)
            )->count(),
            'categories' => $this->categoryStats($from, $to),
        ];
    }

    private function soldItems(CarbonInterface $from, CarbonInterface $to)
    {
        $query = SoldItem::where('sells_items.valid', 1)
            ->join('sells', fn ($join) => $join
                ->on('sells.id', '=', 'sells_items.sell_id')
                ->where('sells.valid', 1))
            ->whereBetween('sells.created_at', [$from, $to]);

        return $this->scope->apply($query, 'sells.parent_shop_id');
    }

    private function categoryStats(CarbonInterface $from, CarbonInterface $to): array
    {
        $devices     = $this->categories->descendantIds(config('dashboard.category_roots.devices'));
        $accessories = $this->categories->descendantIds(config('dashboard.category_roots.accessories'));

        $stats = [
            'devices'     => $this->productStats($from, $to, $devices),
            'accessories' => $this->productStats($from, $to, $accessories),
            'services'    => $this->serviceStats($from, $to),
        ];

        if ($this->withProfit) {
            $stats['devices']['profit']     = $this->productProfit($from, $to, $devices);
            $stats['accessories']['profit'] = $this->productProfit($from, $to, $accessories);
            $stats['services']['profit']    = $this->serviceProfit($from, $to);
            $stats['totalProfit'] =
                $stats['devices']['profit']
                + $stats['accessories']['profit']
                + $stats['services']['profit'];
        }

        return $stats;
    }

    private function productQuery(CarbonInterface $from, CarbonInterface $to, array $categoryIds)
    {
        return $this->soldItems($from, $to)
            ->leftJoin('items', 'items.id', '=', 'sells_items.item_id')
            ->leftJoin('products', 'products.id', '=', 'items.product_id')
            ->whereIn('products.parent_category_id', $categoryIds);
    }

    /** @return array{revenue: int, count: int} */
    private function productStats(CarbonInterface $from, CarbonInterface $to, array $categoryIds): array
    {
        return [
            'revenue' => (int) $this->productQuery($from, $to, $categoryIds)->sum('sells_items.price'),
            'count'   => $this->productQuery($from, $to, $categoryIds)->count(),
        ];
    }

    /** @return array{revenue: int, count: int} */
    private function serviceStats(CarbonInterface $from, CarbonInterface $to): array
    {
        return [
            'revenue' => (int) $this->soldItems($from, $to)->where('sells_items.service_id', '>', 0)->sum('sells_items.price'),
            'count'   => $this->soldItems($from, $to)->where('sells_items.service_id', '>', 0)->count(),
        ];
    }

    private function productProfit(CarbonInterface $from, CarbonInterface $to, array $categoryIds): int
    {
        $profit = 0;

        $this->productQuery($from, $to, $categoryIds)
            ->with(['item.purchasedItem.tax'])
            ->select('sells_items.*')
            ->chunk(500, function ($items) use (&$profit) {
                $profit += $items->sum(fn (SoldItem $si) => $si->getIncome());
            });

        return (int) $profit;
    }

    private function serviceProfit(CarbonInterface $from, CarbonInterface $to): int
    {
        $profit = 0;

        $this->soldItems($from, $to)
            ->where('sells_items.service_id', '>', 0)
            ->select('sells_items.price', 'sells_items.internal_cost')
            ->chunk(500, function ($items) use (&$profit) {
                $profit += $items->sum(fn (SoldItem $si) => $si->price - $si->internal_cost);
            });

        return (int) $profit;
    }
}