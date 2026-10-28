<?php

namespace App\Livewire\Catalog\Products;

use App\Livewire\Concerns\WithProductForm;
use App\Models\Product;
use Livewire\Component;

class Edit extends Component
{
    use WithProductForm;

    public Product $product;

    public ?int $brandId = null;
    public string $name = '';
    public ?int $categoryId = null;

    public function mount(Product $product): void
    {
        abort_if($product->is_legacy && !auth()->user()->isAdmin(), 403);

        $this->product = $product->load('brand');
        $this->brandId = $product->brand_id;
        $this->name = $product->name;
        $this->categoryId = $product->parent_category_id ?: null;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'categoryId' => 'required|integer|exists:categories,id',
        ], [
            'name.required' => 'Wpisz nazwę produktu.',
            'categoryId.required' => 'Wybierz kategorię.',
        ]);

        if ($this->isDuplicateProduct($this->product->id)) {
            $this->addError('name', 'Taki produkt już istnieje.');
            return;
        }

        $this->product->update([
            'name' => trim($this->name),
            'brand_id' => $this->brandId,
            'parent_category_id' => $this->categoryId,
            'updated_by' => auth()->id(),
        ]);


        session()->flash('toast', [
            'heading' => 'Produkt zaktualizowany',
            'text' => $this->product->fresh()->display_name,
            'variant' => 'success',
        ]);

        $this->redirectRoute('catalog.products.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.catalog.products.edit')
            ->title('Edytuj produkt');
    }
}