<?php

namespace App\Livewire\Catalog\Devices;

use App\Livewire\Concerns\DeniesAccess;
use App\Models\Brand;
use App\Models\Device;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination, DeniesAccess;

    public string $search = '';
    public string $brandFilter = '';

    // Form state
    public ?int $deviceId = null;
    public string $model = '';
    public ?int $brand_id = null;

    // Delete state
    public ?int $deletingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedBrandFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function brands()
    {
        return Brand::orderBy('name')->get(['id', 'name']);
    }

    public function create(): void
    {
        $this->reset('deviceId', 'model', 'brand_id');
        $this->resetValidation();
        Flux::modal('device-form')->show();
    }

    public function edit(int $id): void
    {
        $device = Device::findOrFail($id);
        $this->deviceId = $device->id;
        $this->model = $device->model;
        $this->brand_id = $device->brand_id;
        $this->resetValidation();
        Flux::modal('device-form')->show();
    }

    public function save(): void
    {
        $this->validate([
            'model'    => ['required', 'string', 'max:255'],
            'brand_id' => ['required', 'exists:brands,id'],
        ]);

        Device::updateOrCreate(
            ['id' => $this->deviceId],
            [
                'model'      => $this->model,
                'brand_id'   => $this->brand_id,
                'created_by' => $this->deviceId ? Device::find($this->deviceId)->created_by : auth()->id(),
            ],
        );

        Flux::toast(
            variant: 'success',
            text: $this->deviceId ? 'Urządzenie zaktualizowane.' : 'Urządzenie dodane.',
        );

        Flux::modal('device-form')->close();
        $this->reset('deviceId', 'model', 'brand_id');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('device-delete')->show();
    }

    public function delete(): void
    {
        $device = Device::findOrFail($this->deletingId);

        if ($device->variants()->exists()) {
            Flux::toast(variant: 'danger', text: 'Nie można usunąć — urządzenie ma przypisane produkty.');
            Flux::modal('device-delete')->close();
            return;
        }

        $device->delete();
        Flux::toast(text: 'Urządzenie usunięte.');
        Flux::modal('device-delete')->close();
        $this->reset('deletingId');
    }

    public function render()
    {
        $query = Device::with('brand')->withCount('variants');

        if ($this->search !== '') {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('model', 'like', "%{$search}%")
                  ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$search}%"));
            });
        }

        if ($this->brandFilter !== '') {
            $query->where('brand_id', (int) $this->brandFilter);
        }

        $devices = $query->orderBy('model')->paginate(25);

        return view('livewire.catalog.devices.index', [
            'devices' => $devices,
        ]);
    }   
}