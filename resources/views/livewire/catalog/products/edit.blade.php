<div class="max-w-4xl space-y-8">
    <div class="flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('catalog.products.show', $product)" wire:navigate />
        <flux:heading size="xl">Edytuj: {{ $product->display_name }}</flux:heading>
    </div>

    {{-- Section 1: product data, saved with its own button --}}
    <flux:card class="space-y-6">
        <div>
            <flux:heading size="lg">Dane produktu</flux:heading>
            <flux:text size="sm">Zmiany zapisujesz przyciskiem na dole tej sekcji.</flux:text>
        </div>

        @if($showNewBrand)
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <flux:input wire:model="newBrandName" label="Nowa marka" placeholder="Nazwa marki..." />
                </div>
                <flux:button wire:click="createBrand" variant="primary" size="sm">Dodaj</flux:button>
                <flux:button wire:click="$set('showNewBrand', false)" variant="ghost" size="sm">Anuluj</flux:button>
            </div>
        @else
            <div class="flex items-end gap-2">
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

        <div class="flex justify-end">
            <flux:button wire:click="save" variant="primary">Zapisz dane produktu</flux:button>
        </div>
    </flux:card>

    {{-- Section 2: variants, every change is saved immediately --}}
    <flux:card class="space-y-4">
        <div class="flex items-start justify-between">
            <div>
                <flux:heading size="lg">Warianty</flux:heading>
                <flux:text size="sm">Każda zmiana wariantu zapisuje się od razu.</flux:text>
            </div>
            @unless($showVariantForm)
                <flux:button size="sm" variant="primary" icon="plus" wire:click="openCreateVariant">Dodaj wariant</flux:button>
            @endunless
        </div>

        @if($showVariantForm)
            <div class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm">{{ $editingVariantId ? 'Edytuj wariant #' . $editingVariantId : 'Nowy wariant' }}</flux:heading>

                <flux:input wire:model="variantEan" label="EAN (opcjonalnie)" placeholder="5901234123457" class="w-56" />

                <flux:label>Urządzenia (puste = wariant uniwersalny)</flux:label>

                @if(!empty($selectedDevices))
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($selectedDevices as $device)
                            <flux:badge size="sm" color="cyan" class="gap-1">
                                {{ $device['full_name'] }}
                                <button type="button" wire:click="removeDevice({{ $device['id'] }})" class="ml-1 text-zinc-400 hover:text-zinc-600">
                                    <flux:icon.x-mark variant="micro" class="size-3" />
                                </button>
                            </flux:badge>
                        @endforeach
                    </div>
                @endif

                <div class="flex flex-wrap items-end gap-2">
                    <div class="relative min-w-64 flex-1" x-data="{ open: false }" @click.outside="open = false">
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
                            class="absolute z-50 mt-1 max-h-48 w-full overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            @if($this->deviceResults->isNotEmpty())
                                @foreach($this->deviceResults as $device)
                                    <button
                                        type="button"
                                        wire:click="addDevice({{ $device->id }})"
                                        @click="open = false"
                                        class="w-full px-3 py-2 text-left text-sm text-zinc-900 hover:bg-zinc-100 dark:text-zinc-100 dark:hover:bg-zinc-800"
                                    >
                                        {{ $device->full_name }}
                                    </button>
                                @endforeach
                            @elseif(strlen($deviceSearch) >= 2)
                                <div class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">
                                    Nie znaleziono.
                                    <flux:modal.trigger name="create-device">
                                        <button type="button" class="text-blue-500 hover:underline">Dodaj nowe urządzenie</button>
                                    </flux:modal.trigger>
                                </div>
                            @endif
                        </div>
                    </div>

                    <flux:modal.trigger name="create-device">
                        <flux:button variant="ghost" size="sm" icon="plus">Nowe urządzenie</flux:button>
                    </flux:modal.trigger>
                </div>

                @error('variant') <p class="text-sm text-red-500">{{ $message }}</p> @enderror

                <div class="flex gap-2">
                    <flux:button wire:click="saveVariant" variant="primary" size="sm">Zapisz wariant</flux:button>
                    <flux:button wire:click="closeVariantForm" variant="ghost" size="sm">Anuluj</flux:button>
                </div>
            </div>
        @endif

        @error('variants') <p class="text-sm text-red-500">{{ $message }}</p> @enderror

        <flux:table>
            <flux:table.columns>
                <flux:table.column class="w-16">ID</flux:table.column>
                <flux:table.column>Kompatybilność</flux:table.column>
                <flux:table.column class="w-40">EAN</flux:table.column>
                <flux:table.column class="w-28 text-center">Przedmiotów</flux:table.column>
                <flux:table.column class="w-24"></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($this->variants as $variant)
                    <flux:table.row wire:key="variant-{{ $variant->id }}">
                        <flux:table.cell class="tabular-nums text-zinc-400">{{ $variant->id }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                @forelse($variant->devices as $device)
                                    <flux:badge size="sm" color="zinc">{{ $device->full_name }}</flux:badge>
                                @empty
                                    <flux:badge size="sm" color="blue">Uniwersalny</flux:badge>
                                @endforelse
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="font-mono text-sm text-zinc-500">{{ $variant->ean ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-center tabular-nums text-zinc-500">{{ $variant->items_count }}</flux:table.cell>
                        <flux:table.cell class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button size="sm" variant="ghost" icon="pencil" wire:click="openEditVariant({{ $variant->id }})" title="Edytuj" />
                                @if($variant->items_count === 0 && $this->variants->count() > 1)
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="trash"
                                        class="text-red-500"
                                        wire:click="deleteVariant({{ $variant->id }})"
                                        wire:confirm="Usunąć ten wariant?"
                                        title="Usuń"
                                    />
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-zinc-500">Brak wariantów</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <div class="flex justify-end">
        <flux:button :href="route('catalog.products.show', $product)" wire:navigate>Gotowe</flux:button>
    </div>

    <livewire:catalog.create-device-modal />
</div>