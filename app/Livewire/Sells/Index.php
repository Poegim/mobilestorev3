<?php

namespace App\Livewire\Sells;

use App\Enums\PaymentMethod;
use App\Models\Sell;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?Shop $shop = null;

    #[Url]
    public string $search = '';

    #[Url]
    public string $paymentMethod = '';

    #[Url]
    public string $valid = '1';

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

    public function updatedValid(): void
    {
        $this->resetPage();
    }

    public function updatedPeriod(): void
    {
        if ($this->period !== 'custom') {
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

        if ($this->valid !== 'all') {
            $query->where('sells.valid', (int) $this->valid);
        }

        if ($this->paymentMethod !== '') {
            $query->where('sells.payment_method', (int) $this->paymentMethod);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('sells.id', $this->search)
                  ->orWhereHas('soldItems.item.product', fn ($pq) =>
                      $pq->where('name', 'like', "%{$this->search}%")
                  );
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
            'sells' => $sells,
        ]);
    }
}