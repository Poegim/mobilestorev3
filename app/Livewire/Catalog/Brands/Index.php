<?php

namespace App\Livewire\Catalog\Brands;

use App\Models\Brand;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Brands')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $deleteId = null;

    // Null means we are creating a new brand.
    public ?int $brandId = null;

    public string $name = '';

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'name')->ignore($this->brandId),
            ],
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset('brandId', 'name');
        $this->resetValidation();

        Flux::modal('brand-form')->show();
    }

    public function edit(int $id): void
    {
        $brand = Brand::findOrFail($id);

        $this->brandId = $brand->id;
        $this->name = $brand->name;
        $this->resetValidation();

        Flux::modal('brand-form')->show();
    }

    public function save(): void
    {
        $this->validate();

        Brand::updateOrCreate(['id' => $this->brandId], ['name' => $this->name]);

        Flux::modal('brand-form')->close();
        Flux::toast('Brand saved.');
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;

        Flux::modal('brand-delete')->show();
    }

    public function delete(): void
    {
        // Server-side guard: a brand with products must never be deleted.
        if (DB::table('products')->where('brand_id', $this->deleteId)->exists()) {
            Flux::modal('brand-delete')->close();
            Flux::toast('Cannot delete a brand that has products.', variant: 'danger');

            return;
        }

        Brand::whereKey($this->deleteId)->delete();

        $this->reset('deleteId');
        Flux::modal('brand-delete')->close();
        Flux::toast('Brand deleted.');
    }

    public function render(): View
    {
        $brands = Brand::query()
            ->addSelect([
                'products_count' => DB::table('products')
                    ->selectRaw('count(*)')
                    ->whereColumn('products.brand_id', 'brands.id'),
            ])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.catalog.brands.index', ['brands' => $brands]);
    }
}