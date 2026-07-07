<?php

namespace App\Livewire\Items;

use App\Enums\ItemStatus;
use App\Models\Category;
use App\Models\Item;
use App\Models\Shop;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Brand;
use Livewire\Attributes\Url;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '1';
    public string $category = '';
    public string $period = 'all';    

    public ?Shop $shop = null;
    
    #[Url]
    public string $brand = '';

    public function mount(?Shop $shop = null): void
    {
        $this->shop = $shop;
    }

    #[Computed(persist: true)]
    public function categoryTree(): array
    {
        return $this->getCategoryTree();
    }


    public function updatedBrand(): void
    {
        $this->resetPage();
    }

    public function updatedPeriod(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
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

    private function getCategoryTree(): array
    {
        $all = Category::orderBy('name')->get();
        $tree = [];

        // top-level parents (skip root "Przedmioty")
        $topLevel = $all->where('parent_category_id', 1);

        foreach ($topLevel as $parent) {
            $children = $this->getChildrenRecursive($all, $parent->id);
            $tree[] = [
                'id' => $parent->id,
                'name' => $parent->name,
                'children' => $children,
            ];
        }

        return $tree;
    }

    private function getChildrenRecursive($all, int $parentId, int $depth = 0): array
    {
        $result = [];
        $children = $all->where('parent_category_id', $parentId)->sortBy('name');

        foreach ($children as $child) {
            $result[] = [
                'id' => $child->id,
                'name' => $child->name,
                'depth' => $depth,
            ];
            $result = array_merge($result, $this->getChildrenRecursive($all, $child->id, $depth + 1));
        }

        return $result;
    }

    /**
     * Return the given category id plus all descendant category ids.
     * Products are attached to leaf categories, so filtering by a parent
     * node (e.g. "Urządzenia") must include the whole subtree.
     */
    private function descendantCategoryIds(int $categoryId): array
    {
        $all = Category::get(['id', 'parent_category_id']);
        $ids = [$categoryId];
        $stack = [$categoryId];

        while ($stack) {
            $parent = array_pop($stack);
            foreach ($all->where('parent_category_id', $parent) as $child) {
                $ids[] = (int) $child->id;
                $stack[] = (int) $child->id;
            }
        }

        return $ids;
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