<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Shop;
use App\Enums\ItemStatus;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TopProducts extends Component
{
    public ?Shop $shop = null;

    public int $topLimit = 10;
    public int $trendingLimit = 5;

    public function mount(?Shop $shop = null): void
    {
        $this->shop = $shop;
    }

    // ── Global lists ────────────────────────────

    #[Computed]
    public function globalTop(): array
    {
        return $this->buildRanking(
            since: Carbon::now()->subMonths(12),
            limit: $this->topLimit,
            shopScope: null,
            stockShop: $this->shop,
        );
    }

    #[Computed]
    public function globalTrending(): array
    {
        $excludeIds = collect($this->globalTop)->pluck('product_id')->toArray();

        return $this->buildRanking(
            since: Carbon::now()->subMonths(3),
            limit: $this->trendingLimit,
            shopScope: null,
            excludeProductIds: $excludeIds,
            stockShop: $this->shop,
        );
    }

    // ── Per-shop lists (only when shop is set) ──

    #[Computed]
    public function shopTop(): array
    {
        if (!$this->shop) {
            return [];
        }

        return $this->buildRanking(
            since: Carbon::now()->subMonths(12),
            limit: $this->topLimit,
            shopScope: $this->shop,
            stockShop: $this->shop,
        );
    }

    #[Computed]
    public function shopTrending(): array
    {
        if (!$this->shop) {
            return [];
        }

        $excludeIds = collect($this->shopTop)->pluck('product_id')->toArray();

        return $this->buildRanking(
            since: Carbon::now()->subMonths(3),
            limit: $this->trendingLimit,
            shopScope: $this->shop,
            excludeProductIds: $excludeIds,
            stockShop: $this->shop,
        );
    }

    // ── Query builder ───────────────────────────

    private function buildRanking(
        Carbon $since,
        int $limit,
        ?Shop $shopScope,
        ?Shop $stockShop = null,
        array $excludeProductIds = [],
    ): array {
        $query = DB::table('sells_items')
            ->join('sells', fn ($j) => $j
                ->on('sells.id', '=', 'sells_items.sell_id')
                ->where('sells.valid', 1)
            )
            ->join('items', 'items.id', '=', 'sells_items.item_id')
            ->join('products', 'products.id', '=', 'items.product_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->where('sells_items.valid', 1)
            ->where('sells_items.item_id', '>', 0)
            ->where('sells.created_at', '>=', $since)
            ->groupBy('products.id', 'products.name', 'brands.name')
            ->orderByDesc('sold_count');

        $selects = [
            'products.id as product_id',
            'products.name as product_name',
            'brands.name as brand_name',
            DB::raw('COUNT(*) as sold_count'),
        ];

        // Shop scope for sales data
        if ($shopScope) {
            $query->where('sells.parent_shop_id', $shopScope->id);
        } else {
            $user = auth()->user();
            if (!$user->isAdmin()) {
                $shopIds = $user->shops()->pluck('shops.id')->toArray();
                if (!empty($shopIds)) {
                    $query->whereIn('sells.parent_shop_id', $shopIds);
                }
            }
        }

        // Current stock count at the viewed shop
        if ($stockShop) {
            $selects[] = DB::raw(
                '(SELECT COUNT(*) FROM items AS stock
                  WHERE stock.product_id = products.id
                    AND stock.parent_shop_id = ' . (int) $stockShop->id . '
                    AND stock.status = ' . ItemStatus::Store->value . '
                 ) as in_stock'
            );
        }

        $query->select($selects);

        if (!empty($excludeProductIds)) {
            $query->whereNotIn('products.id', $excludeProductIds);
        }

        return $query
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'product_id' => $row->product_id,
                'name'       => $row->product_name,
                'brand'      => $row->brand_name ?? '',
                'count'      => (int) $row->sold_count,
                'in_stock'   => isset($row->in_stock) ? (int) $row->in_stock : null,
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.top-products');
    }
}