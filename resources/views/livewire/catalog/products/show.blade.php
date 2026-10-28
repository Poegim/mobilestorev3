@php $product = $this->product; @endphp

<div>
    <div class="mb-4 flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('catalog.products.index')" wire:navigate />
        <flux:heading size="xl">{{ $product->display_name }}</flux:heading>
        @if($product->is_legacy)
            <flux:badge size="sm" color="zinc">Legacy</flux:badge>
        @endif
        @if($product->trashed())
            <flux:badge size="sm" color="amber">Zarchiwizowany</flux:badge>
        @endif
        @if($this->canManage)
            <flux:button size="sm" variant="ghost" icon="pencil" class="ml-auto" :href="route('catalog.products.edit', $product)" wire:navigate>
                Edytuj produkt
            </flux:button>
        @endif
    </div>

    {{-- Details --}}
    <flux:card class="mb-6">
        <div class="grid grid-cols-2 gap-6 lg:grid-cols-4">
            <div>
                <flux:subheading>Marka</flux:subheading>
                <flux:heading class="mt-1">{{ $product->brand?->name ?? '—' }}</flux:heading>
            </div>
            <div>
                <flux:subheading>Kategoria</flux:subheading>
                <flux:heading class="mt-1">{{ $product->category?->name ?? '—' }}</flux:heading>
            </div>
            <div>
                <flux:subheading>Na magazynie (wszystkie sklepy)</flux:subheading>
                <flux:heading class="mt-1 tabular-nums">{{ $this->variants->sum('in_store_count') + $this->unassigned['in_store'] }}</flux:heading>
            </div>
            <div>
                <flux:subheading>Dodał</flux:subheading>
                @if($product->creator)
                    <div class="mt-1 flex items-center gap-2">
                        <flux:avatar size="sm">{{ $product->creator->initials() }}</flux:avatar>
                        <flux:heading>{{ $product->creator->name }}</flux:heading>
                    </div>
                @else
                    <flux:heading class="mt-1">—</flux:heading>
                @endif
                <flux:text size="sm" class="mt-1">{{ $product->created_at?->format('d.m.Y H:i') ?? '' }}</flux:text>
            </div>
        </div>
    </flux:card>

    {{-- Variants --}}
    <div class="mb-3 flex items-center justify-between">
        <flux:heading size="lg">Warianty</flux:heading>
        @if($this->canManage && ! $showVariantForm)
            <flux:button size="sm" variant="primary" icon="plus" wire:click="openCreateVariant">Dodaj wariant</flux:button>
        @endif
    </div>

    @if($showVariantForm)
        <flux:card class="mb-4 space-y-3">
            <flux:heading size="sm">{{ $editingVariantId ? 'Edytuj wariant #' . $editingVariantId : 'Nowy wariant' }}</flux:heading>
            <flux:input wire:model="variantEan" label="EAN (opcjonalnie)" placeholder="5901234123457" class="w-56" />
            
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
                            <div class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">Nie znaleziono</div>
                        @endif
                    </div>
                </div>

                <flux:button x-data x-on:click="Flux.modal('create-device').show()" variant="ghost" size="sm" icon="plus">
                    Nowe urządzenie
                </flux:button>
            </div>

            @error('variant') <p class="text-sm text-red-500">{{ $message }}</p> @enderror

            <div class="flex gap-2">
                <flux:button wire:click="saveVariant" variant="primary" size="sm">Zapisz wariant</flux:button>
                <flux:button wire:click="closeVariantForm" variant="ghost" size="sm">Anuluj</flux:button>
            </div>
        </flux:card>
    @endif

    @error('variants') <p class="mb-3 text-sm text-red-500">{{ $message }}</p> @enderror

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-16">ID</flux:table.column>
            <flux:table.column>Kompatybilność</flux:table.column>
            <flux:table.column class="w-40">EAN</flux:table.column>
            <flux:table.column class="w-32 text-center">Na magazynie</flux:table.column>
            <flux:table.column class="w-32 text-center">Wszystkich</flux:table.column>
            <flux:table.column class="w-28"></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->variants as $variant)
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
                    <flux:table.cell class="text-center tabular-nums">{{ $variant->in_store_count }}</flux:table.cell>
                    <flux:table.cell class="text-center tabular-nums text-zinc-500">{{ $variant->items_count }}</flux:table.cell>
                    <flux:table.cell class="text-right">
                        @if($this->canManage)
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
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @endforeach

            @if($this->unassigned['total'] > 0)
                <flux:table.row>
                    <flux:table.cell class="text-zinc-400">—</flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">Bez wariantu (uniwersalne i stare przedmioty)</flux:table.cell>
                    <flux:table.cell class="text-center tabular-nums">{{ $this->unassigned['in_store'] }}</flux:table.cell>
                    <flux:table.cell class="text-center tabular-nums text-zinc-500">{{ $this->unassigned['total'] }}</flux:table.cell>
                    <flux:table.cell></flux:table.cell>
                </flux:table.row>
            @endif

            @if($this->variants->isEmpty() && $this->unassigned['total'] === 0)
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center text-zinc-500">
                        Ten produkt nie ma wariantów, jest uniwersalny.
                    </flux:table.cell>
                </flux:table.row>
            @endif
        </flux:table.rows>
    </flux:table>

    <livewire:catalog.create-device-modal />
</div>