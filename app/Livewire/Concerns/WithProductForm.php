<?php

namespace App\Livewire\Concerns;

use App\Models\Brand;
use App\Models\Device;
use Livewire\Attributes\Computed;

trait WithProductForm
{
    // Pick-or-create brand
    public string $newBrandName = '';
    public bool $showNewBrand = false;

    // Pick-or-create devices
    public string $deviceSearch = '';
    public array $selectedDevices = [];
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

        $name = trim($this->newBrandName);
        $existing = Brand::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        if ($existing) {
            $this->brandId = $existing->id;
        } else {
            $brand = Brand::create(['name' => $name]);
            $this->brandId = $brand->id;
        }

        $this->newBrandName = '';
        $this->showNewBrand = false;
    }

    /** @return int[] Sorted device IDs from selection */
    protected function getSelectedDeviceIds(): array
    {
        return collect($this->selectedDevices)->pluck('id')->sort()->values()->all();
    }

    /** Check if a product with same brand + name + devices already exists */
    protected function isDuplicateProduct(?int $excludeId = null): bool
    {
        $query = \App\Models\Product::current()
            ->where('brand_id', $this->brandId)
            ->where('name', trim($this->name));

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $selectedIds = $this->getSelectedDeviceIds();

        foreach ($query->get() as $product) {
            $existingIds = $product->devices()->pluck('devices.id')->sort()->values()->all();
            if ($existingIds === $selectedIds) {
                return true;
            }
        }

        return false;
    }
}