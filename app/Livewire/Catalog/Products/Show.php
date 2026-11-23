<?php

namespace App\Livewire\Catalog\Products;

use App\Enums\ItemStatus;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Show extends Component
{
    #[Locked]
    public int $productId;

    public function mount(Product $product): void
    {
        $this->productId = $product->id;
    }

    #[Computed]
    public function product(): Product
    {
        // withTrashed: archived products can still be opened from the catalog list
        return Product::withTrashed()
            ->with(['brand', 'category', 'creator'])
            ->findOrFail($this->productId);
    }

    /** Legacy products can only be edited by admins, archived ones by nobody. */
    #[Computed]
    public function canEdit(): bool
    {
        return ! $this->product->trashed()
            && (! $this->product->is_legacy || auth()->user()->isAdmin());
    }

    #[Computed]
    public function variants()
    {
        return $this->product->variants()
            ->with('devices.brand')
            ->withCount([
                'items',
                'items as in_store_count' => fn ($q) => $q->where('status', ItemStatus::Store->value),
            ])
            ->orderBy('id')
            ->get();
    }

    /** Items of this product that have no variant (everything from legacy). */
    #[Computed]
    public function unassigned(): array
    {
        $query = $this->product->items()->whereNull('variant_id');

        return [
            'total'    => (clone $query)->count(),
            'in_store' => (clone $query)->where('status', ItemStatus::Store->value)->count(),
        ];
    }

    public function render()
    {
        return view('livewire.catalog.products.show')
            ->title($this->product->display_name);
    }
}