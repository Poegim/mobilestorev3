<?php

namespace App\Livewire\Items;

use App\Enums\ItemStatus;
use App\Livewire\Concerns\WithCategoryFilter;
use App\Models\Brand;
use App\Models\Item;
use App\Models\Shop;
use Livewire\Attributes\Computed;
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

    /** Visible columns — initialized from localStorage via Alpine on first render */
    public array $columns = ['product', 'category', 'imei', 'condition', 'price', 'purchase', 'dsnp', 'status'];
    
    #[Url]
    public string $brand = '';

    #[Url]
    public bool $imeiSearch = false;

    /** All available column definitions */
    #[Computed]
    public function columnDefs(): array
    {
        return [
            'product'   => 'Produkt',
            'category'  => 'Kategoria',
            'imei'      => 'IMEI',
            'condition' => 'Stan',
            'price'     => 'Ceny',
            'purchase'  => 'Zakup',
            'dsnp'      => 'DSNP',
            'status'    => 'Status',
        ];
    }

    #[Computed]
    public function selectedCategoryLabel(): string
    {
        if ($this->category === '') {
            return 'Wszystkie kategorie';
        }

        foreach ($this->categoryTree as $group) {
            if ((string) $group['id'] === $this->category) {
                return 'Wszystkie ' . $group['name'];
            }
            foreach ($group['children'] as $child) {
                if ((string) $child['id'] === $this->category) {
                    return $child['name'];
                }
            }
        }

        return 'Wszystkie kategorie';
    }

    public function mount(?Shop $shop = null): void
    {
        $this->shop = $shop;
    }

    public function updated(string $property): void
    {
        // Return to the first page whenever a filter changes
        if (in_array($property, ['search', 'status', 'brand', 'period', 'imeiSearch'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $query = Item::with(['product.brand', 'product.category', 'condition', 
        'purchasedItem.tax',  
        'purchasedItem.purchase.contact',
        'soldItem',
        'shop'
        ]);
        
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

            if ($this->imeiSearch) {
                // Dedicated IMEI mode: match a fragment anywhere in the IMEI
                // (e.g. the last 4 digits). Only fires from 3+ chars.
                // NOTE: leading wildcard can't use the index — this is a full
                // scan, acceptable for an occasional deliberate lookup.
                if (mb_strlen($search) >= 3) {
                    $query->where('feature_imei', 'like', "%{$search}%");
                }
            } else {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                      ->orWhere('feature_imei', 'like', "{$search}%");
                    if (ctype_digit($search)) {
                        $q->orWhere('id', (int) $search);
                    }
                });
            }
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