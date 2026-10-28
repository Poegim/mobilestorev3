<?php

namespace App\Livewire\Catalog\Products;

use App\Enums\ItemStatus;
use App\Models\Device;
use App\Models\Product;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Validation\Rule;

class Show extends Component
{
    #[Locked]
    public int $productId;

    // Inline variant form; editingVariantId = null means a new variant
    public bool $showVariantForm = false;
    public ?int $editingVariantId = null;
    public string $deviceSearch = '';
    public string $variantEan = '';

    /** @var array<int, array{id: int, full_name: string}> */
    public array $selectedDevices = [];

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

    /** Legacy products can only be changed by admins, archived ones by nobody. */
    #[Computed]
    public function canManage(): bool
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

    /** Items of this product that have no variant (universal items and everything from legacy). */
    #[Computed]
    public function unassigned(): array
    {
        $query = $this->product->items()->whereNull('variant_id');

        return [
            'total'    => (clone $query)->count(),
            'in_store' => (clone $query)->where('status', ItemStatus::Store->value)->count(),
        ];
    }

    #[Computed]
    public function deviceResults()
    {
        if (strlen($this->deviceSearch) < 2) {
            return collect();
        }

        $selectedIds = collect($this->selectedDevices)->pluck('id')->all();

        return Device::with('brand')
            ->search($this->deviceSearch)
            ->whereNotIn('devices.id', $selectedIds)
            ->limit(20)
            ->get();
    }

    // ─── Variant form ────────────────────────────────────────

    public function openCreateVariant(): void
    {
        abort_unless($this->canManage, 403);

        $this->resetVariantForm();
        $this->showVariantForm = true;
    }

    public function openEditVariant(int $variantId): void
    {
        abort_unless($this->canManage, 403);

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

    public function addDevice(int $deviceId): void
    {
        $device = Device::with('brand')->find($deviceId);
        if (! $device || collect($this->selectedDevices)->contains('id', $deviceId)) {
            return;
        }

        $this->selectedDevices[] = ['id' => $device->id, 'full_name' => $device->full_name];
        $this->deviceSearch = '';
        $this->resetErrorBag('variant');
    }

    public function removeDevice(int $deviceId): void
    {
        $this->selectedDevices = collect($this->selectedDevices)
            ->reject(fn ($d) => $d['id'] === $deviceId)
            ->values()
            ->all();
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
        abort_unless($this->canManage, 403);

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
        $deviceIds = collect($this->selectedDevices)
            ->pluck('id')
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
        abort_unless($this->canManage, 403);

        $variant = $this->product->variants()->findOrFail($variantId);

        // A new-catalog product must always keep at least one variant
        if (! $this->product->is_legacy && $this->product->variants()->count() <= 1) {
            $this->addError('variants', 'Produkt musi mieć co najmniej jeden wariant. Zamiast tego zarchiwizuj produkt.');
            return;
        }

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
        $this->reset(['showVariantForm', 'editingVariantId', 'deviceSearch', 'selectedDevices', 'variantEan']);
        $this->resetErrorBag('variant');
    }

    public function render()
    {
        return view('livewire.catalog.products.show')
            ->title($this->product->display_name);
    }
}