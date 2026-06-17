<?php

namespace App\Livewire\Sells;

use App\Enums\PaymentMethod;
use App\Models\Sell;
use App\Models\Shop;
use Carbon\Carbon;
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

    /** @return array{int, int} Unix timestamp range for current period */
    private function getTimestampRange(): array
    {
        return match ($this->period) {
            'today' => [
                Carbon::today()->startOfDay()->timestamp,
                Carbon::today()->endOfDay()->timestamp,
            ],
            'week' => [
                Carbon::now()->startOfWeek()->timestamp,
                Carbon::now()->endOfWeek()->timestamp,
            ],
            'month' => [
                Carbon::now()->startOfMonth()->timestamp,
                Carbon::now()->endOfMonth()->timestamp,
            ],
            'year' => [
                Carbon::now()->startOfYear()->timestamp,
                Carbon::now()->endOfYear()->timestamp,
            ],
            'custom' => [
                $this->dateFrom ? Carbon::parse($this->dateFrom)->startOfDay()->timestamp : 0,
                $this->dateTo ? Carbon::parse($this->dateTo)->endOfDay()->timestamp : Carbon::now()->endOfDay()->timestamp,
            ],
            default => [0, Carbon::now()->endOfDay()->timestamp], // 'all'
        };
    }

    public function render()
    {
        $query = Sell::query()
            ->with(['shop', 'soldItems' => fn ($q) => $q->where('valid', 1), 'soldItems.item.product.brand'])
            ->selectRaw('sells.*,
                (SELECT COUNT(*) FROM sells_items WHERE sells_items.sell_id = sells.id AND sells_items.valid = 1) as items_count,
                (SELECT COALESCE(SUM(price), 0) FROM sells_items WHERE sells_items.sell_id = sells.id AND sells_items.valid = 1) as total_price
            ');

        if ($this->shop) {
            $query->where('parent_shop_id', $this->shop->id);
        } else {
            $user = auth()->user();
            if (!$user->isAdmin()) {
                $shopIds = $user->shops()->pluck('shops.id');
                $query->whereIn('parent_shop_id', $shopIds);
            }
        }

        // Period filter
        if ($this->period !== 'all') {
            [$from, $to] = $this->getTimestampRange();
            $query->whereBetween('added_timestamp', [$from, $to]);
        }

        if ($this->valid !== 'all') {
            $query->where('valid', (int) $this->valid);
        }

        if ($this->paymentMethod !== '') {
            $query->where('payment_method', (int) $this->paymentMethod);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('id', $this->search)
                  ->orWhereHas('soldItems.item.product', fn ($pq) =>
                      $pq->where('name', 'like', "%{$this->search}%")
                  );
            });
        }

        $sells = $query->orderByDesc('added_timestamp')->paginate($this->perPage);

        return view('livewire.sells.index', [
            'sells' => $sells,
        ]);
    }
}