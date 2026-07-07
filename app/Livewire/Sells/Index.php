<?php

namespace App\Livewire\Sells;

use App\Enums\ItemStatus;
use App\Enums\PaymentMethod;
use App\Models\Item;
use App\Models\Sell;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use App\Livewire\Concerns\WithCategoryFilter;

class Index extends Component
{
    use WithPagination;
    use WithCategoryFilter;

    public ?Shop $shop = null;

    #[Url]
    public string $search = '';

    #[Url]
    public string $paymentMethod = '';

    #[Url]
    public string $status = 'valid';

    #[Url]
    public string $period = 'today';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public int $perPage = 25;

    public function mount(?Shop $shop = null): void
    {
        $this->shop = $shop;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentMethod(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPeriod(): void
    {
        if ($this->period === 'custom') {
            $this->dateFrom = now()->format('Y-m-d');
            $this->dateTo = now()->format('Y-m-d');
        } else {
            $this->dateFrom = '';
            $this->dateTo = '';
        }
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function paymentMethods(): array
    {
        return PaymentMethod::cases();
    }

    /** @return array{Carbon, Carbon} Date range for current period */
    private function getDateRange(): array
    {
        return match ($this->period) {
            'today' => [
                Carbon::today()->startOfDay(),
                Carbon::today()->endOfDay(),
            ],
            'week' => [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ],
            'month' => [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ],
            'year' => [
                Carbon::now()->startOfYear(),
                Carbon::now()->endOfYear(),
            ],
            'custom' => [
                $this->dateFrom ? Carbon::parse($this->dateFrom)->startOfDay() : Carbon::createFromTimestamp(0),
                $this->dateTo ? Carbon::parse($this->dateTo)->endOfDay() : Carbon::now()->endOfDay(),
            ],
            default => [Carbon::createFromTimestamp(0), Carbon::now()->endOfDay()],
        };
    }

    /**
     * Apply shared filters to a sells query builder.
     */
    private function applyFilters($query)
    {
        if ($this->shop) {
            $query->where('sells.parent_shop_id', $this->shop->id);
        } else {
            $user = auth()->user();
            if (!$user->isAdmin()) {
                $shopIds = $user->shops()->pluck('shops.id');
                $query->whereIn('sells.parent_shop_id', $shopIds);
            }
        }

        // Period filter
        if ($this->period !== 'all') {
            [$from, $to] = $this->getDateRange();
            $query->whereBetween('sells.created_at', [$from, $to]);
        }

        // Status filter: valid (all non-cancelled), completed, no_bill, cancelled, all
        match ($this->status) {
            'valid'     => $query->where('sells.valid', 1),
            'completed' => $query->where('sells.valid', 1)->whereNotNull('sells.bill_printed_at'),
            'no_bill'   => $query->where('sells.valid', 1)->whereNull('sells.bill_printed_at'),
            'cancelled' => $query->where('sells.valid', 0),
            default     => null, // 'all' — no filter
        };

        if ($this->paymentMethod !== '') {
            $query->where('sells.payment_method', (int) $this->paymentMethod);
        }

        if ($this->category !== '') {
            $categoryIds = $this->descendantCategoryIds((int) $this->category);
            $query->whereHas('soldItems.item.product', fn ($pq) => $pq->whereIn('parent_category_id', $categoryIds));
        }

        if ($this->search !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('soldItems.item.product', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('soldItems.item', fn ($it) => $it->where('feature_imei', 'like', "{$search}%"));

                // Numeric input: sell ID or item ID (both indexed)
                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search)
                      ->orWhereHas('soldItems', fn ($si) => $si->where('item_id', (int) $search));
                }
            });
        }

        

        return $query;
    }

    /**
     * Summary: total revenue and breakdown by payment method.
     * Runs a single aggregate query against the filtered sell set.
     *
     * @return array{total: int, count: int, byMethod: array<int, array{label: string, total: int, count: int}>}
     */
    #[Computed]
    public function summary(): array
    {
        // Subquery: IDs of sells matching current filters
        $filteredIds = $this->applyFilters(Sell::query())->select('sells.id');

        $rows = DB::table('sells')
            ->join('sells_items', 'sells_items.sell_id', '=', 'sells.id')
            ->whereIn('sells.id', $filteredIds)
            ->where('sells_items.valid', 1)
            ->groupBy('sells.payment_method')
            ->select([
                'sells.payment_method',
                DB::raw('SUM(sells_items.price) as total'),
                DB::raw('COUNT(DISTINCT sells.id) as sell_count'),
            ])
            ->get();

        $total = 0;
        $count = 0;
        $byMethod = [];

        foreach ($rows as $row) {
            $pm = PaymentMethod::tryFrom($row->payment_method);
            $byMethod[$row->payment_method] = [
                'label' => $pm?->label() ?? "#{$row->payment_method}",
                'total' => (int) $row->total,
                'count' => (int) $row->sell_count,
            ];
            $total += (int) $row->total;
            $count += (int) $row->sell_count;
        }

        return [
            'total' => $total,
            'count' => $count,
            'byMethod' => $byMethod,
        ];
    }

    /**
     * Build a remaining-stock lookup for sold items on the current page.
     * Returns ["product_id:shop_id" => remaining_count].
     *
     * @param  \Illuminate\Support\Collection  $sells
     * @return array<string, int>
     */
    private function buildStockMap($sells): array
    {
        // Collect unique (product_id, shop_id) pairs from sold items
        $pairs = collect();

        foreach ($sells as $sell) {
            foreach ($sell->soldItems as $si) {
                if ($si->item?->product_id) {
                    $pairs->push([
                        'product_id' => $si->item->product_id,
                        'shop_id'    => $sell->parent_shop_id,
                    ]);
                }
            }
        }

        $pairs = $pairs->unique(fn ($p) => $p['product_id'] . ':' . $p['shop_id']);

        if ($pairs->isEmpty()) {
            return [];
        }

        // Single query: count items still in store, grouped by product + shop
        $counts = Item::where('status', ItemStatus::Store)
            ->where(function ($q) use ($pairs) {
                foreach ($pairs as $pair) {
                    $q->orWhere(fn ($sq) => $sq
                        ->where('product_id', $pair['product_id'])
                        ->where('parent_shop_id', $pair['shop_id'])
                    );
                }
            })
            ->groupBy('product_id', 'parent_shop_id')
            ->select('product_id', 'parent_shop_id', DB::raw('COUNT(*) as remaining'))
            ->get();

        $map = [];
        foreach ($counts as $row) {
            $map[$row->product_id . ':' . $row->parent_shop_id] = (int) $row->remaining;
        }

        // Pairs with zero remaining won't appear in the query result — fill them in
        foreach ($pairs as $pair) {
            $key = $pair['product_id'] . ':' . $pair['shop_id'];
            if (!isset($map[$key])) {
                $map[$key] = 0;
            }
        }

        return $map;
    }

    public function render()
    {
        $query = Sell::query()
            ->with(['shop', 'soldItems' => fn ($q) => $q->where('valid', 1), 'soldItems.item.product.brand'])
            ->selectRaw('sells.*,
                (SELECT COUNT(*) FROM sells_items WHERE sells_items.sell_id = sells.id AND sells_items.valid = 1) as items_count,
                (SELECT COALESCE(SUM(price), 0) FROM sells_items WHERE sells_items.sell_id = sells.id AND sells_items.valid = 1) as total_price
            ');

        $this->applyFilters($query);

        $sells = $query->orderByDesc('created_at')->paginate($this->perPage);

        return view('livewire.sells.index', [
            'sells'    => $sells,
            'stockMap' => $this->buildStockMap($sells),
        ]);
    }
}