<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Katalog produktów</flux:heading>
        <div class="flex items-center gap-3">
            <flux:badge size="lg">{{ $products->total() }} produktów</flux:badge>
            <flux:button variant="primary" icon="plus" size="sm" :href="route('catalog.products.create')" wire:navigate>
                Nowy produkt
            </flux:button>
        </div>
    </div>

    
    <div class="mb-4 flex flex-wrap gap-2 items-center">

        <div class="flex-1 min-w-56">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Szukaj marki, nazwy lub modelu urządzenia..."
                icon="magnifying-glass"
                clearable
            />
        </div>

        <flux:select wire:model.live="brandFilter" class="w-48">
            <option value="">Wszystkie marki</option>
            @foreach($this->brands as $brand)
                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="perPage" class="w-20" size="sm">
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </flux:select>

        <flux:select wire:model.live="catalog" class="w-48">
            <option value="current">Nowe produkty</option>
            <option value="legacy">Legacy</option>
            <option value="all">Wszystkie</option>
        </flux:select>
    </div>

    <flux:table :paginate="$products">
        <flux:table.columns>
            <flux:table.column>ID</flux:table.column>
            <flux:table.column>Marka</flux:table.column>
            <flux:table.column>Nazwa</flux:table.column>
            <flux:table.column>Kompatybilność</flux:table.column>
            <flux:table.column>Dodał</flux:table.column>
            <flux:table.column>Data</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($products as $product)
                <flux:table.row>
                    <flux:table.cell class="tabular-nums text-zinc-400">{{ $product->id }}</flux:table.cell>
                    <flux:table.cell>{{ $product->brand?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell variant="strong">{{ $product->name }}</flux:table.cell>
                    <flux:table.cell>
                        @if($product->devices->isNotEmpty())
                            <div class="flex flex-wrap gap-1">
                                @foreach($product->devices as $device)
                                    <flux:badge size="sm" color="zinc">{{ $device->full_name }}</flux:badge>
                                @endforeach
                            </div>
                        @else
                            <span class="text-zinc-400 text-xs">Uniwersalny</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500 text-sm">{{ $product->creator?->login ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-zinc-500 text-sm tabular-nums">{{ $product->created_at?->format('d.m.Y') ?? '—' }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-500">
                        Brak produktów w katalogu
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>