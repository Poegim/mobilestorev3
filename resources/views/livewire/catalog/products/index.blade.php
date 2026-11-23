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

        <flux:select wire:model.live="catalog" class="w-48">
            <option value="current">Nowe produkty</option>
            <option value="legacy">Legacy</option>
            <option value="archived">Zarchiwizowane</option>
            <option value="all">Wszystkie</option>
        </flux:select>

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
    </div>

    <flux:table :paginate="$products" class="text-sm">
    <flux:table.columns class="text-xs uppercase tracking-wide">
        <flux:table.column class="w-14!">ID</flux:table.column>
        <flux:table.column>Marka</flux:table.column>
        <flux:table.column>Nazwa</flux:table.column>
        <flux:table.column>Warianty</flux:table.column>
        <flux:table.column>Kategoria</flux:table.column>
        <flux:table.column class="w-28! text-right">Akcje</flux:table.column>
    </flux:table.columns>

    <flux:table.rows>
        @forelse($products as $product)
            @php($trashed = $product->trashed())
            <flux:table.row
                @class([
                    'py-2! transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50',
                    'opacity-50 grayscale' => $trashed,
                ])
            >

                {{-- ID --}}
                <flux:table.cell class="py-2!">
                    <a href="{{ route('catalog.products.show', $product) }}" wire:navigate
                       class="tabular-nums text-xs text-zinc-400 transition-colors hover:text-zinc-700 dark:hover:text-zinc-200">
                        #{{ $product->id }}
                    </a>
                </flux:table.cell>

                {{-- Marka --}}
                <flux:table.cell class="py-2!">
                    @if($product->brand)
                        <a href="{{ route('catalog.brands.index') }}" wire:navigate
                           class="inline-flex items-center gap-1 text-zinc-500 transition-colors hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
                            <flux:icon.tag variant="micro" class="size-3 opacity-60" />
                            {{ $product->brand->name }}
                        </a>
                    @else
                        <span class="text-zinc-300 dark:text-zinc-600">—</span>
                    @endif
                </flux:table.cell>

                {{-- Nazwa --}}
                <flux:table.cell class="py-2!">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('catalog.products.show', $product) }}" wire:navigate
                           class="font-medium text-zinc-900 transition-colors hover:underline hover:decoration-zinc-300 hover:underline-offset-4 dark:text-zinc-100">
                            {{ $product->name }}
                        </a>
                        @if($trashed)
                            <flux:badge size="xs" color="amber">usunięty</flux:badge>
                        @elseif($product->is_legacy)
                            <flux:tooltip content="Produkt legacy" position="top">
                                <flux:badge size="xs" color="zinc" variant="outline" class="cursor-help">L</flux:badge>
                            </flux:tooltip>
                        @endif
                    </div>
                </flux:table.cell>

                {{-- Warianty --}}
                <flux:table.cell class="py-2!">
                    @if($product->variants->isNotEmpty())
                        <div class="flex flex-wrap items-center gap-1">
                            @foreach($product->variants->take(3) as $variant)
                                <span class="rounded-md bg-zinc-100 px-1.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                    {{ $variant->display_name }}
                                </span>
                            @endforeach
                            @if($product->variants->count() > 3)
                                <span class="text-xs font-medium text-zinc-400">+{{ $product->variants->count() - 3 }}</span>
                            @endif
                        </div>
                    @else
                        <span class="rounded-md border border-dashed border-zinc-300 px-1.5 py-0.5 text-xs text-zinc-400 dark:border-zinc-700">
                            uniwersalny
                        </span>
                    @endif
                </flux:table.cell>

                {{-- Kategoria --}}
                <flux:table.cell class="py-2!">
                    @if($product->category)
                        <span class="text-zinc-500 dark:text-zinc-400">{{ $product->category->name }}</span>
                    @else
                        <span class="text-zinc-300 dark:text-zinc-600">—</span>
                    @endif
                </flux:table.cell>

                {{-- Akcje – zawsze widoczne --}}
                <flux:table.cell class="py-2! text-right">
                    <div class="flex items-center justify-end gap-0.5">
                        @if($trashed)
                            <flux:tooltip content="Przywróć" position="top">
                                <flux:button size="xs" variant="ghost" icon="arrow-uturn-left"
                                    wire:click="restoreProduct({{ $product->id }})"
                                    class="text-emerald-600 hover:text-emerald-500 dark:text-emerald-400" />
                            </flux:tooltip>
                        @else
                            <flux:tooltip content="Podejrzyj" position="top">
                                <flux:button size="xs" variant="ghost" icon="eye"
                                    :href="route('catalog.products.show', $product)" wire:navigate
                                    class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200" />
                            </flux:tooltip>

                            @if(! $product->is_legacy || auth()->user()->isAdmin())
                                <flux:tooltip content="Edytuj" position="top">
                                    <flux:button size="xs" variant="ghost" icon="pencil-square"
                                        :href="route('catalog.products.edit', $product)" wire:navigate
                                        class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200" />
                                </flux:tooltip>
                            @endif

                            @if($product->items_count === 0 && ! $product->is_legacy)
                                <flux:tooltip content="Archiwizuj" position="top">
                                    <flux:button size="xs" variant="ghost" icon="archive-box-x-mark"
                                        wire:click="archiveProduct({{ $product->id }})"
                                        wire:confirm="Czy na pewno chcesz zarchiwizować {{ $product->name }}?"
                                        class="text-zinc-400 hover:text-amber-500" />
                                </flux:tooltip>
                            @endif
                        @endif
                    </div>
                </flux:table.cell>
            </flux:table.row>
        @empty
            <flux:table.row>
                <flux:table.cell colspan="6" class="py-16! text-center">
                    <div class="mx-auto flex max-w-xs flex-col items-center gap-3">
                        <span class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon.cube variant="outline" class="size-6 text-zinc-400" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Brak produktów w katalogu</p>
                            <p class="mt-1 text-xs text-zinc-400">Dodaj pierwszy produkt — pojawi się tutaj.</p>
                        </div>
                        <flux:button size="sm" variant="primary" icon="plus"
                            :href="route('catalog.products.create')" wire:navigate>
                            Nowy produkt
                        </flux:button>
                    </div>
                </flux:table.cell>
            </flux:table.row>
        @endforelse
    </flux:table.rows>
</flux:table>
</div>