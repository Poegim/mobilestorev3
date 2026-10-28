<div>
    <flux:modal name="create-product" class="w-full max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">Nowy produkt</flux:heading>

            {{-- Brand --}}
            @if($showNewBrand)
                <div class="flex gap-2 items-end">
                    <div class="flex-1">
                        <flux:input wire:model="newBrandName" label="Nowa marka" placeholder="Nazwa marki..." autofocus />
                    </div>
                    <flux:button wire:click="createBrand" variant="primary" size="sm">Dodaj</flux:button>
                    <flux:button wire:click="$set('showNewBrand', false)" variant="ghost" size="sm">Anuluj</flux:button>
                </div>
            @else
                <div class="flex gap-2 items-end">
                    <div class="flex-1">
                        <flux:select wire:model.live="brandId" label="Marka">
                            <option value="">Bez marki</option>
                            @foreach($this->brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <flux:button wire:click="$set('showNewBrand', true)" variant="ghost" size="sm" icon="plus">Nowa</flux:button>
                </div>
            @endif

            <flux:input wire:model="name" label="Nazwa produktu" placeholder="np. Flexible Glass" />

            <flux:select wire:model="categoryId" label="Kategoria">
                <option value="">Wybierz kategorię...</option>
                @foreach($this->categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </flux:select>

            {{-- Devices --}}
            <div>
                <flux:label>Kompatybilność z urządzeniami (opcjonalne)</flux:label>

                @if(!empty($selectedDevices))
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach($selectedDevices as $device)
                            <flux:badge size="sm" color="zinc" class="gap-1">
                                {{ $device['full_name'] }}
                                <button type="button" wire:click="removeDevice({{ $device['id'] }})" class="ml-1 text-zinc-400 hover:text-zinc-600">
                                    <flux:icon.x-mark variant="micro" class="size-3" />
                                </button>
                            </flux:badge>
                        @endforeach
                    </div>
                @endif

                <div class="mt-2 relative" x-data="{ open: false }" @click.outside="open = false">
                    <flux:input
                        wire:model.live.debounce.300ms="deviceSearch"
                        placeholder="Szukaj urządzenia..."
                        size="sm"
                        @focus="open = true"
                        @input="open = true"
                    />
                    <div
                        x-show="open"
                        x-cloak
                        class="absolute z-50 mt-1 w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-lg max-h-48 overflow-y-auto"
                    >
                        @if($this->deviceResults->isNotEmpty())
                            @foreach($this->deviceResults as $device)
                                <button
                                    type="button"
                                    wire:click="addDevice({{ $device->id }})"
                                    @click="open = false"
                                    class="w-full px-3 py-2 text-left text-sm text-zinc-900 dark:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                                >
                                    {{ $device->full_name }}
                                </button>
                            @endforeach
                        @elseif(strlen($deviceSearch) >= 2)
                            <div class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">Nie znaleziono</div>
                        @endif
                    </div>
                </div>
            </div>

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
                <flux:button wire:click="save" variant="primary">Zapisz produkt</flux:button>
            </div>
        </div>
    </flux:modal>
</div>