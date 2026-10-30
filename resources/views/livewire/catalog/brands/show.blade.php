<div class="space-y-6">
    {{-- Header with breadcrumb navigation --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <flux:button :href="route('catalog.brands.index')" variant="ghost" icon="arrow-left" wire:navigate />
            <div>
                <flux:heading size="xl" level="1">{{ $brand->name }}</flux:heading>
                <flux:subheading>Produkty przypisane do marki</flux:subheading>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <flux:badge size="lg" color="zinc">{{ $products->total() }} {{ trans_choice('produkt|produkty|produktów', $products->total()) }}</flux:badge>
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Szukaj produktu..." class="w-64" />
        </div>
    </div>

    <flux:table :paginate="$products">
        <flux:table.columns>
            <flux:table.column>ID</flux:table.column>
            <flux:table.column sortable>Nazwa</flux:table.column>
            <flux:table.column>GTIN</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($products as $product)
                <flux:table.row :key="$product->id">
                    <flux:table.cell class="text-zinc-400 text-xs tabular-nums">{{ $product->id }}</flux:table.cell>
                    <flux:table.cell variant="strong">
                        <flux:link :href="route('catalog.products.show', $product)" wire:navigate>
                            {{ $product->display_name }}
                        </flux:link>
                    </flux:table.cell>
                    <flux:table.cell class="font-mono text-sm text-zinc-500">{{ $product->gtin ?: '—' }}</flux:table.cell>
                    <flux:table.cell align="end">
                        @if(!$product->is_legacy || auth()->user()->isAdmin())
                            <flux:button :href="route('catalog.products.edit', $product)" size="sm" variant="ghost" icon="pencil-square" wire:navigate />
                        @else
                            <flux:icon.lock-closed class="size-4 text-zinc-300 dark:text-zinc-600" title="Produkt archiwalny — tylko admin" />
                        @endif                        
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">
                        <div class="py-8 text-center">
                            <flux:icon.archive-box class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                            <flux:text class="mt-2">
                                @if($search)
                                    Brak produktów pasujących do "{{ $search }}"
                                @else
                                    Ta marka nie ma jeszcze przypisanych produktów.
                                @endif
                            </flux:text>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>