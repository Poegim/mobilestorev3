<?php

namespace App\Livewire\Catalog;

use App\Models\Brand;
use App\Models\Device;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CreateDeviceModal extends Component
{
    public ?int $brandId = null;
    public string $model = '';

    #[Computed]
    public function brands()
    {
        return Brand::orderBy('name')->get();
    }

    public function save(): void
    {
        $this->validate([
            'brandId' => 'required|exists:brands,id',
            'model' => 'required|string|max:255',
        ], [
            'brandId.required' => 'Wybierz markę.',
            'model.required' => 'Wpisz model urządzenia.',
        ]);

        $device = Device::firstOrCreate(
            ['brand_id' => $this->brandId, 'model' => trim($this->model)],
            ['created_by' => auth()->id()]
        );

        if (!$device->wasRecentlyCreated) {
            $this->addError('model', 'To urządzenie już istnieje.');
            return;
        }

        $this->dispatch('device-created', id: $device->id, fullName: $device->full_name);
        $this->reset(['brandId', 'model']);
        $this->resetValidation();

        \Flux\Flux::modals()->close();
    }

    public function render()
    {
        return view('livewire.catalog.create-device-modal');
    }
}