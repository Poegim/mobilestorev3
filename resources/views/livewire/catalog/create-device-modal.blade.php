<div>
    <flux:modal name="create-device" class="w-full max-w-sm">
        <div class="space-y-4">
            <flux:heading size="lg">Nowe urządzenie</flux:heading>

            <flux:select wire:model="brandId" label="Marka">
                <option value="">Wybierz markę...</option>
                @foreach($this->brands as $brand)
                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="model" label="Model" placeholder="np. iPhone 17 Pro" />

            @if($errors->any())
                <div class="space-y-1">
                    @foreach($errors->all() as $error)
                        <p class="text-sm text-red-500">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Anuluj</flux:button>
                </flux:modal.close>
                <flux:button wire:click="save" variant="primary">Zapisz urządzenie</flux:button>
            </div>
        </div>
    </flux:modal>
</div>