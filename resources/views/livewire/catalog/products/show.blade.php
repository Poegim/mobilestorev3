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
        @if($this->canEdit)
            <flux:button variant="primary" icon="pencil" class="ml-auto" :href="route('catalog.products.edit', $product)" wire:navigate>
                Edytuj produkt i warianty
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

    {{-- Variants (read only) --}}
    <flux:heading size="lg" class="mb-3">Warianty</flux:heading>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-16">ID</flux:table.column>
            <flux:table.column>Kompatybilność</flux:table.column>
            <flux:table.column class="w-40">EAN</flux:table.column>
            <flux:table.column class="w-32 text-center">Na magazynie</flux:table.column>
            <flux:table.column class="w-32 text-center">Wszystkich</flux:table.column>
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
                </flux:table.row>
            @endforeach

            @if($this->unassigned['total'] > 0)
                <flux:table.row>
                    <flux:table.cell class="text-zinc-400">—</flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">Bez wariantu (stare przedmioty)</flux:table.cell>
                    <flux:table.cell></flux:table.cell>
                    <flux:table.cell class="text-center tabular-nums">{{ $this->unassigned['in_store'] }}</flux:table.cell>
                    <flux:table.cell class="text-center tabular-nums text-zinc-500">{{ $this->unassigned['total'] }}</flux:table.cell>
                </flux:table.row>
            @endif

            @if($this->variants->isEmpty() && $this->unassigned['total'] === 0)
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center text-zinc-500">Brak wariantów</flux:table.cell>
                </flux:table.row>
            @endif
        </flux:table.rows>
    </flux:table>
</div>