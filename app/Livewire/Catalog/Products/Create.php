<?php

namespace App\Livewire\Catalog\Products;

use App\Livewire\Concerns\WithProductForm;
use App\Models\Product;
use Livewire\Component;

class Create extends Component
{
    use WithProductForm;

    public ?int $brandId = null;
    public string $name = '';
    public ?int $categoryId = null;

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'categoryId' => 'required|integer|exists:categories,id',
        ], [
            'name.required' => 'Wpisz nazwę produktu.',
            'categoryId.required' => 'Wybierz kategorię.',
        ]);

        if ($this->isDuplicateProduct()) {
            $this->addError('name', 'Taki produkt już istnieje.');
            return;
        }

        $product = Product::create([
            'name' => trim($this->name),
            'brand_id' => $this->brandId,
            'parent_category_id' => $this->categoryId,
            'is_legacy' => false,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->createInitialVariant($product->id);

        session()->flash('toast', [
            'duration' => false,
            'heading' => 'Produkt dodany',
            'text' => $product->display_name,
            'variant' => 'success',
        ]);

        $this->redirectRoute('catalog.products.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.catalog.products.create')
            ->title('Nowy produkt');
    }
}