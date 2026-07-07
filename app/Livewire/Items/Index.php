<?php

namespace App\Livewire\Items;

use App\Enums\ItemStatus;
use App\Livewire\Concerns\WithCategoryFilter;
use App\Models\Brand;
use App\Models\Item;
use App\Models\Shop;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    use WithCategoryFilter;

    public string $search = '';
    public string $status = '1';
    public string $period = 'all';    

    public ?Shop $shop = null;
    
    #[Url]
    public string $brand = '';

    public function mount(?Shop $shop = null): void
    {
        $this->shop = $shop;
    }

    public function updatedBrand(): void
    {
        $this->resetPage();
    }

    public function updatedPeriod(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Item::with(['product.brand', 'product.category', 'condition', 
        'purchasedItem.tax',  
        'purchasedItem.purchase.contact']);
        
        if ($this->shop) {
            $query->where('parent_shop_id', $this->shop->id);
        } else {
            // only shops user has access to
            $user = auth()->user();
            if (!$user->isAdmin()) {
                $shopIds = $user->shops()->pluck('shops.id');
                $query->whereIn('parent_shop_id', $shopIds);
            }
        }

        if ($this->status !== 'all') {
            $query->where('status', (int) $this->status);
        }

        if ($this->category !== '') {
            $categoryIds = $this->descendantCategoryIds((int) $this->category);
            $query->whereHas('product', fn ($q) => $q->whereIn('parent_category_id', $categoryIds));
        }

        if ($this->search !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                  ->orWhere('feature_imei', 'like', "{$search}%");

                // Numeric input can also be an item ID (indexed PK)
                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        if ($this->period !== '' && $this->period !== 'all') {
            $period = \App\Enums\Period::tryFrom($this->period);
            if ($period) {
                $range = $period->dateRange();
                if ($range) {
                    $query->whereBetween('displaced_at', $range);
                }
            }
        }

        if ($this->brand !== '') {
            $query->whereHas('product', fn ($q) => $q->where('brand_id', (int) $this->brand));
        }

        $items = $query->orderByDesc('id')->paginate(25);

        return view('livewire.items.index', [
            'items' => $items,
            'statuses' => ItemStatus::cases(),
            'brands' => Brand::orderBy('name')->get(['id', 'name']), // pass plainly, no persisted computed

        ]);
    }
}