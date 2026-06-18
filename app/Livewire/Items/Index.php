<?php

namespace App\Livewire\Items;

use App\Enums\ItemStatus;
use App\Models\Category;
use App\Models\Item;
use App\Models\Shop;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '1';
    public string $category = '';
    public string $period = 'all';    

    public ?Shop $shop = null;

    public function mount(?Shop $shop = null): void
    {
        $this->shop = $shop;
    }

    #[Computed(persist: true)]
    public function categoryTree(): array
    {
        return $this->getCategoryTree();
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

    public function render()
    {
        $query = Item::with(['product.brand', 'product.category', 'condition', 'purchasedItem.purchase.contact']);
        
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
            $query->whereHas('product', fn ($q) => $q->where('parent_category_id', (int) $this->category));
        }

        if ($this->search !== '') {
            $query->whereHas('product', function ($q) {
                $q->where('name', 'like', "%{$this->search}%");
            });
        }

        if ($this->period !== '' && $this->period !== 'all') {
            $period = \App\Enums\Period::tryFrom($this->period);
            if ($period && $period->startDate()) {
                $query->where('displaced_at', '>=', $period->startDate());
            }
        }

        $items = $query->orderByDesc('id')->paginate(25);

        return view('livewire.items.index', [
            'items' => $items,
            'statuses' => ItemStatus::cases(),
        ]);
    }
}