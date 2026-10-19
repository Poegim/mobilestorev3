<?php

namespace App\Livewire\Catalog\Products;

use App\Models\Brand;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $brandFilter = '';

    #[Url]
    public int $perPage = 25;

    #[Url]
    public string $catalog = 'current';

    

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'brandFilter', 'perPage', 'catalog'])) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function brands()
    {
        return Brand::orderBy('name')->get();
    }

    public function archiveProduct(int $id): void
    {
        $product = Product::find($id);
        if (!$product || $product->is_legacy) return;

        $product->delete(); // soft delete
    }

    public function restoreProduct(int $id): void
    {
        $product = Product::withTrashed()->find($id);
        if (!$product) return;

        $product->restore();
    }

    public function render()
    {
        $query = match ($this->catalog) {
            'legacy' => Product::legacy(),
            'archived' => Product::onlyTrashed()->current(),
            'all' => Product::withTrashed(),
            default => Product::current(),
        };

        $query = $query
        ->with(['brand', 'devices', 'creator', 'category'])
        ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
        ->selectRaw('products.*, (SELECT COUNT(*) FROM items WHERE items.product_id = products.id) as items_count');

        if ($this->search !== '') {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->whereRaw("CONCAT(COALESCE(brands.name, ''), ' ', products.name) LIKE ?", ["%{$search}%"])
                  ->orWhere('products.name', 'like', "%{$search}%")
                  ->orWhere('brands.name', 'like', "%{$search}%")
                  ->orWhereHas('devices', fn ($d) => $d->where('model', 'like', "%{$search}%"));
            });
        }

        if ($this->brandFilter !== '') {
            $query->where('products.brand_id', (int) $this->brandFilter);
        }

        $products = $query
            ->orderBy('id', 'desc')
            ->orderBy('brands.name')
            ->orderBy('products.name')
            ->paginate($this->perPage);

        return view('livewire.catalog.products.index', [
            'products' => $products,
        ])->title('Katalog produktów');
    }
}