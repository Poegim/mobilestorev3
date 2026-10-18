<?php

namespace App\Livewire\Catalog\Products;

use App\Models\Brand;
use App\Models\Device;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Create extends Component
{
    // Product fields
    public ?int $brandId = null;
    public string $name = '';
    public ?int $categoryId = null;

    // Pick-or-create brand
    public string $newBrandName = '';
    public bool $showNewBrand = false;

    // Pick-or-create devices
    public string $deviceSearch = '';
    public array $selectedDevices = []; // [{id, full_name}]
    public bool $showNewDevice = false;
    public ?int $newDeviceBrandId = null;
    public string $newDeviceModel = '';

    #[Computed]
    public function brands()
    {
        return Brand::orderBy('name')->get();
    }

    #[Computed]
    public function categories()
    {
        return \App\Models\Category::orderBy('name')->get();
    }

    #[Computed]
    public function deviceResults()
    {
        if (strlen($this->deviceSearch) < 2) {
            return collect();
        }

        $search = $this->deviceSearch;
        $selectedIds = collect($this->selectedDevices)->pluck('id')->all();

        return Device::with('brand')
            ->leftJoin('brands', 'devices.brand_id', '=', 'brands.id')
            ->whereNotIn('devices.id', $selectedIds)
            ->where(function ($q) use ($search) {
                $q->where('devices.model', 'like', "%{$search}%")
                  ->orWhere('brands.name', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(COALESCE(brands.name, ''), ' ', devices.model) LIKE ?", ["%{$search}%"]);
            })
            ->select('devices.*')
            ->orderBy('brands.name')
            ->orderBy('devices.model')
            ->limit(30)
            ->get();
    }

    public function addDevice(int $deviceId): void
    {
        $device = Device::with('brand')->find($deviceId);
        if (!$device) return;

        // Prevent duplicates
        if (collect($this->selectedDevices)->contains('id', $deviceId)) return;

        $this->selectedDevices[] = ['id' => $device->id, 'full_name' => $device->full_name];
        $this->deviceSearch = '';
    }

    public function removeDevice(int $deviceId): void
    {
        $this->selectedDevices = collect($this->selectedDevices)
            ->reject(fn ($d) => $d['id'] === $deviceId)
            ->values()
            ->all();
    }

    public function createDevice(): void
    {
        $this->validate([
            'newDeviceModel' => 'required|string|max:255',
        ], [
            'newDeviceModel.required' => 'Wpisz model urządzenia.',
        ]);

        $device = Device::firstOrCreate(
            ['brand_id' => $this->newDeviceBrandId, 'model' => trim($this->newDeviceModel)],
            ['created_by' => auth()->id()]
        );

        $this->selectedDevices[] = ['id' => $device->id, 'full_name' => $device->full_name];
        $this->newDeviceModel = '';
        $this->newDeviceBrandId = null;
        $this->showNewDevice = false;
    }

    public function createBrand(): void
    {
        $this->validate([
            'newBrandName' => 'required|string|max:128',
        ], [
            'newBrandName.required' => 'Wpisz nazwę marki.',
        ]);

        $brand = Brand::firstOrCreate(['name' => trim($this->newBrandName)]);
        $this->brandId = $brand->id;
        $this->newBrandName = '';
        $this->showNewBrand = false;
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

        // Check for duplicate: same brand + name + exact same devices
        $existing = Product::current()
            ->where('brand_id', $this->brandId)
            ->where('name', trim($this->name))
            ->get();

        $selectedIds = collect($this->selectedDevices)->pluck('id')->sort()->values()->all();

        foreach ($existing as $product) {
            $existingIds = $product->devices()->pluck('devices.id')->sort()->values()->all();
            if ($existingIds === $selectedIds) {
                $this->addError('name', 'Taki produkt z tą samą kompatybilnością już istnieje.');
                return;
            }
        }

        $product = Product::create([
            'name' => trim($this->name),
            'brand_id' => $this->brandId,
            'parent_category_id' => $this->categoryId ?? 0,
            'is_legacy' => false,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        if (!empty($selectedIds)) {
            $product->devices()->sync($selectedIds);
        }

        session()->flash('toast', [
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