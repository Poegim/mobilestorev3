<?php

namespace App\Livewire\Purchases;

use App\Enums\PaymentMethod;
use App\Enums\Period;
use App\Models\Purchase;
use App\Models\Shop;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $paymentMethod = '';
    public string $valid = '';
    public string $period = 'today';

    public ?Shop $shop = null;

    public function mount(?Shop $shop = null): void
    {
        $this->shop = $shop;
    }

    public function updatedSearch(): void
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
        $this->resetPage();
    }

    public function render()
    {
        $query = Purchase::with(['shop', 'contact', 'purchasedItems.tax'])
            ->orderByDesc('id');

        // Scope to shop if provided
        if ($this->shop) {
            $query->where('parent_shop_id', $this->shop->id);
        } else {
            $user = auth()->user();
            if (!$user->isAdmin()) {
                $shopIds = $user->shops()->pluck('shops.id');
                $query->whereIn('parent_shop_id', $shopIds);
            }
        }

        // Filter by contact name or invoice number
        if ($this->search !== '') {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('contact', fn ($c) => $c->where('name', 'like', "%{$search}%"))
                  ->orWhere('invoice_number', 'like', "%{$search}%");
            });
        }

        // Filter by payment method
        if ($this->paymentMethod !== '') {
            $query->where('payment_method', (int) $this->paymentMethod);
        }

        // Filter by validity
        if ($this->valid !== '') {
            $query->where('valid', (int) $this->valid);
        }

        // Filter by date period
        if ($this->period !== '' && $this->period !== 'all') {
            $period = Period::tryFrom($this->period);
            if ($period && $period->startDate()) {
                $query->where('created_at', '>=', $period->startDate());
            }
        }

        $purchases = $query->paginate(25);

        return view('livewire.purchases.index', [
            'purchases' => $purchases,
            'paymentMethods' => PaymentMethod::cases(),
            'periods' => Period::cases(),
        ]);
    }
}