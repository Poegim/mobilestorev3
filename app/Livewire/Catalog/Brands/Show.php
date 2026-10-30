<?php

namespace App\Livewire\Catalog\Brands;

use App\Models\Brand;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Brand')]
class Show extends Component
{
    use WithPagination;

    public Brand $brand;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $products = $this->brand->products()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.catalog.brands.show', ['products' => $products]);
    }
}