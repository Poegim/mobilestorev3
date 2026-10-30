<?php

namespace App\Livewire\Catalog\Products;

use App\Livewire\Concerns\DeniesAccess;
use App\Livewire\Concerns\WithProductForm;
use App\Models\Product;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Edit extends Component
{
    // Brand creation + device search/selection (deviceSearch, selectedDevices, addDevice, removeDevice)
    use WithProductForm;
    use DeniesAccess;

    public Product $product;

    // Section 1: product data, saved with its own button
    public ?int $brandId = null;
    public string $name = '';
    public ?int $categoryId = null;

    // Section 2: variants, every change saved immediately; editingVariantId = null means a new variant
    public bool $showVariantForm = false;
    public ?int $editingVariantId = null;
    public string $variantEan = '';

    public function mount(Product $product): void
    {
        $this->denyIf(
            $product->is_legacy && !auth()->user()->isAdmin(),
            'Ten produkt jest archiwalny i dostępny tylko dla administratorów.'
        );

        $this->product = $product->load('brand');
        $this->brandId = $product->brand_id;
        $this->name = $product->name;
        $this->categoryId = $product->parent_category_id ?: null;
    }

    // ─── Product data ────────────────────────────────────────

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

        // Reload so the header shows the new brand/name
        $this->product->refresh();

        Flux::toast(text: $this->product->display_name, heading: 'Dane produktu zapisane', variant: 'success');
    }

    // ─── Variants ────────────────────────────────────────────

    #[Computed]
    public function variants()
    {
        return $this->product->variants()
            ->with('devices.brand')
            ->withCount('items')
            ->orderBy('id')
            ->get();
    }

    public function openCreateVariant(): void
    {
        $this->resetVariantForm();
        $this->showVariantForm = true;
    }

    public function openEditVariant(int $variantId): void
    {
        $variant = $this->product->variants()->with('devices.brand')->findOrFail($variantId);

        $this->resetVariantForm();
        $this->editingVariantId = $variant->id;
        $this->variantEan = $variant->ean ?? '';
        $this->selectedDevices = $variant->devices
            ->map(fn ($d) => ['id' => $d->id, 'full_name' => $d->full_name])
            ->all();
        $this->showVariantForm = true;
    }

    public function closeVariantForm(): void
    {
        $this->resetVariantForm();
    }

    /** A device created in CreateDeviceModal lands straight in the open variant form. */
    #[On('device-created')]
    public function onDeviceCreated(int $id, string $fullName): void
    {
        if ($this->showVariantForm && ! collect($this->selectedDevices)->contains('id', $id)) {
            $this->selectedDevices[] = ['id' => $id, 'full_name' => $fullName];
        }
    }

    public function saveVariant(): void
    {
        $this->validate([
            'variantEan' => [
                'nullable',
                'digits_between:8,14',
                Rule::unique('product_variants', 'ean')->ignore($this->editingVariantId),
            ],
        ], [
            'variantEan.digits_between' => 'EAN musi mieć od 8 do 14 cyfr.',
            'variantEan.unique' => 'Ten EAN jest już przypisany do innego wariantu.',
        ]);

        // No devices = universal variant
        $deviceIds = collect($this->getSelectedDeviceIds())
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values();

        // The same set of devices must not exist twice within one product
        $duplicate = $this->product->variants()
            ->when($this->editingVariantId, fn ($q) => $q->where('id', '!=', $this->editingVariantId))
            ->with('devices')
            ->get()
            ->contains(fn ($v) => $v->devices->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all() === $deviceIds->all());

        if ($duplicate) {
            $this->addError('variant', 'Wariant z tymi urządzeniami już istnieje.');
            return;
        }

        $wasEditing = (bool) $this->editingVariantId;

        $variant = $wasEditing
            ? $this->product->variants()->findOrFail($this->editingVariantId)
            : $this->product->variants()->create(['created_by' => auth()->id()]);

        $variant->update(['ean' => trim($this->variantEan) ?: null]);
        $variant->devices()->sync($deviceIds->all());

        unset($this->variants);
        $this->resetVariantForm();

        Flux::toast(
            text: $variant->fresh('devices.brand')->display_name,
            heading: $wasEditing ? 'Wariant zaktualizowany' : 'Wariant dodany',
            variant: 'success',
        );
    }

    public function deleteVariant(int $variantId): void
    {
        $variant = $this->product->variants()->findOrFail($variantId);

        // A new-catalog product must always keep at least one variant
        if (! $this->product->is_legacy && $this->product->variants()->count() <= 1) {
            $this->addError('variants', 'Produkt musi mieć co najmniej jeden wariant. Zamiast tego zarchiwizuj produkt.');
            return;
        }

        // Any item, in any status, blocks deletion
        if ($variant->items()->exists()) {
            $this->addError('variants', 'Ten wariant ma przypisane przedmioty, nie można go usunąć.');
            return;
        }

        $deletedName = $variant->display_name;

        $variant->devices()->detach();
        $variant->delete();

        if ($this->editingVariantId === $variantId) {
            $this->resetVariantForm();
        }

        unset($this->variants);
        Flux::toast(text: $deletedName, heading: 'Wariant usunięty', variant: 'success');
    }

    private function resetVariantForm(): void
    {
        $this->reset(['showVariantForm', 'editingVariantId', 'variantEan', 'deviceSearch', 'selectedDevices']);
        $this->resetErrorBag(['variant', 'variantEan']);
    }

    public function render()
    {
        return view('livewire.catalog.products.edit')
            ->title('Edytuj produkt');
    }
}