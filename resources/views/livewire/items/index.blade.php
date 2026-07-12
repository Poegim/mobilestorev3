{{--
    localStorage key: 'items_columns'
    Alpine reads on init, writes on every change via x-effect.
    $wire.columns stays as the source of truth for Livewire rendering.
--}}
<div
    x-data="{
        init() {
            const saved = localStorage.getItem('items_columns');
            if (saved) {
                try {
                    $wire.set('columns', JSON.parse(saved));
                } catch {}
            }
        }
    }"
    x-effect="localStorage.setItem('items_columns', JSON.stringify($wire.columns))"
>
    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Magazyn</flux:heading>
        <flux:badge size="lg" color="zinc">{{ $items->total() }} szt.</flux:badge>
    </div>

    {{-- Toolbar --}}
    <div class="mb-4 flex flex-wrap gap-2 items-center">

        {{-- Search group --}}
        <div class="flex items-center gap-2 flex-1 min-w-64">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="{{ $imeiSearch ? 'Fragment IMEI...' : 'Szukaj produktu lub IMEI...' }}"
                icon="magnifying-glass"
                clearable
            />
            <flux:checkbox wire:model.live="imeiSearch" label="IMEI" />
        </div>

        <flux:separator vertical class="h-8 hidden sm:block" />

        {{-- Filter group --}}
        <flux:select wire:model.live="brand" class="w-36">
            <option value="">Wszystkie marki</option>
            @foreach($brands as $b)
                <option value="{{ $b->id }}">{{ $b->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="period" class="w-36">
            @foreach(\App\Enums\Period::cases() as $p)
                <option value="{{ $p->value }}">{{ $p->label() }}</option>
            @endforeach
        </flux:select>

    <flux:dropdown>
        <button class="flex h-10 w-48 items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 text-sm text-zinc-700 shadow-xs hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 dark:hover:text-white transition-colors">
            <span class="truncate">{{ $this->selectedCategoryLabel }}</span>
            <flux:icon.chevron-down variant="micro" class="size-4 shrink-0 text-zinc-400 dark:text-zinc-400" />
        </button>

        <flux:menu class="w-48 max-h-72 overflow-y-auto">
            <flux:menu.item wire:click="$set('category', '')">
                Wszystkie kategorie
            </flux:menu.item>
            <flux:separator />
            @foreach($this->categoryTree as $group)
                <flux:menu.group :heading="$group['name']">
                    <flux:menu.item wire:click="$set('category', '{{ $group['id'] }}')">
                        Wszystkie {{ $group['name'] }}
                    </flux:menu.item>
                    @foreach($group['children'] as $child)
                        <flux:menu.item wire:click="$set('category', '{{ $child['id'] }}')">
                            {{ str_repeat('  ', $child['depth']) }}{{ $child['name'] }}
                        </flux:menu.item>
                    @endforeach
                </flux:menu.group>
            @endforeach
        </flux:menu>
    </flux:dropdown>

        <flux:select wire:model.live="status" class="w-36">
            <option value="all">Wszystkie statusy</option>
            @foreach($statuses as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </flux:select>

        <flux:separator vertical class="h-8 hidden sm:block" />

        {{-- Column picker --}}
        <flux:dropdown align="end">
            <flux:button icon="adjustments-horizontal" size="sm" variant="ghost" tooltip="Kolumny" />
            <flux:menu class="min-w-40">
                <flux:menu.group heading="Widoczne kolumny">
                    @foreach($this->columnDefs as $key => $label)
                        <flux:menu.item>
                            <label class="flex items-center gap-2 cursor-pointer w-full">
                                <input
                                    type="checkbox"
                                    value="{{ $key }}"
                                    x-bind:checked="$wire.columns.includes('{{ $key }}')"
                                    x-on:change="
                                        const cols = [...$wire.columns];
                                        const val = '{{ $key }}';
                                        const idx = cols.indexOf(val);
                                        if (idx === -1) cols.push(val);
                                        else cols.splice(idx, 1);
                                        $wire.set('columns', cols);
                                    "
                                    class="rounded border-zinc-300 dark:border-zinc-600"
                                />
                                <span>{{ $label }}</span>
                            </label>
                        </flux:menu.item>
                    @endforeach
                </flux:menu.group>
            </flux:menu>
        </flux:dropdown>
    </div>

    {{-- Table --}}
    <flux:table :paginate="$items">
        <flux:table.columns>
            <flux:table.column class="w-12">#</flux:table.column>
            @if(in_array('product', $columns))   <flux:table.column>Produkt</flux:table.column>               @endif
            @if(in_array('category', $columns))  <flux:table.column>Kategoria</flux:table.column>             @endif
            @if(in_array('imei', $columns))      <flux:table.column>IMEI</flux:table.column>                  @endif
            @if(in_array('condition', $columns)) <flux:table.column>Stan</flux:table.column>                  @endif
            @if(in_array('price', $columns))     <flux:table.column>Ceny</flux:table.column>                  @endif
            @if(in_array('purchase', $columns))  <flux:table.column>Zakup</flux:table.column>                 @endif
            @if(in_array('dsnp', $columns))      <flux:table.column title="Dni od przyjęcia na półkę">Dni ↑</flux:table.column> @endif
            @if(in_array('status', $columns))    <flux:table.column>Status</flux:table.column>                @endif
            @if(!$this->shop)                    <flux:table.column class="w-36">Sklep</flux:table.column>     @endif
        </flux:table.columns>

        <flux:table.rows>
            @forelse($items as $item)
                <flux:table.row class="group">

                    {{-- ID — links to item detail when available --}}
                    <flux:table.cell class="text-zinc-400 text-xs tabular-nums">
                        {{ $item->id }}
                    </flux:table.cell>

                    @if(in_array('product', $columns))
                        <flux:table.cell class="whitespace-normal">
                            <div class="font-medium break-words min-w-32">
                                {{ $item->product->brand->name ?? '' }} {{ $item->product->name }}
                            </div>
                        </flux:table.cell>
                    @endif

                    @if(in_array('category', $columns))
                        <flux:table.cell class="text-zinc-500 text-sm">
                            {{ $item->product->category->name ?? '—' }}
                        </flux:table.cell>
                    @endif

                    @if(in_array('imei', $columns))
                        <flux:table.cell class="font-mono text-xs">
                            @if($item->feature_imei)
                                {{-- Click to copy IMEI to clipboard --}}
                                <button
                                    x-data
                                    x-on:click="
                                        navigator.clipboard.writeText('{{ $item->feature_imei }}');
                                        $flux.toast({ text: 'Skopiowano IMEI', variant: 'success' });
                                    "
                                    class="cursor-copy hover:text-zinc-900 dark:hover:text-white transition-colors"
                                    title="Kliknij, aby skopiować"
                                >{{ $item->feature_imei }}</button>
                            @else
                                <span class="text-zinc-300">—</span>
                            @endif
                        </flux:table.cell>
                    @endif

                    @if(in_array('condition', $columns))
                        <flux:table.cell class="text-sm">
                            {{ $item->condition->name ?? '—' }}
                        </flux:table.cell>
                    @endif

                    @if(in_array('price', $columns))
                        @php
                            $pi   = $item->purchasedItem;
                            $sell = $item->getSellingPrice();
                        @endphp
                        <flux:table.cell class="tabular-nums text-xs whitespace-nowrap">
                            <div class="flex flex-col gap-0.5">
                                @if($pi)
                                    {{-- Purchase price (net / gross) --}}
                                    <span class="flex items-center gap-1 text-zinc-400">
                                        <flux:icon name="arrow-down-tray" variant="micro" class="shrink-0 text-red-400" />
                                        {{ number_format($pi->price / 100, 2, ',', ' ') }}
                                        / {{ number_format($pi->getGrossPrice() / 100, 2, ',', ' ') }} zł
                                    </span>
                                @endif
                                {{-- Selling price --}}
                                <span class="flex items-center gap-1 font-medium">
                                    <flux:icon name="arrow-up-tray" variant="micro" class="shrink-0 text-green-500" />
                                    {{ $sell !== null ? number_format($sell / 100, 2, ',', ' ') . ' zł' : '—' }}
                                </span>
                            </div>
                        </flux:table.cell>
                    @endif

                    @if(in_array('purchase', $columns))
                        <flux:table.cell class="text-xs">
                            @if($item->purchasedItem?->purchase)
                                @php $p = $item->purchasedItem->purchase; @endphp
                                <div class="flex flex-col gap-0.5">
                                    <span class="font-mono text-zinc-700 dark:text-zinc-300">
                                        {{ $p->invoice_number ?: '—' }}
                                    </span>
                                    <span class="text-zinc-500 truncate max-w-32" title="{{ $p->contact->name ?? '' }}">
                                        {{ Str::limit($p->contact->name ?? '—', 18) }}
                                    </span>
                                    <span class="text-zinc-400">{{ $p->created_at->format('d.m.Y') }}</span>
                                </div>
                            @else
                                <span class="text-zinc-300">—</span>
                            @endif
                        </flux:table.cell>
                    @endif

                    @if(in_array('dsnp', $columns))
                        <flux:table.cell class="text-xs tabular-nums">
                            @if($item->displaced_at)
                                @php
                                    // Highlight items sitting on shelf for too long (>30 days)
                                    $daysOnShelf = $item->days_on_shelf;
                                    $isStale = $daysOnShelf > 30;
                                @endphp
                                <span @class([
                                    'font-medium',
                                    'text-red-500' => $isStale,
                                    'text-zinc-700 dark:text-zinc-300' => !$isStale,
                                ])>{{ $daysOnShelf }}d</span>
                                <div class="text-zinc-400">{{ $item->displaced_at->format('d.m.Y') }}</div>
                            @else
                                <span class="text-zinc-300">—</span>
                            @endif
                        </flux:table.cell>
                    @endif

                    @if(in_array('status', $columns))
                        <flux:table.cell>
                            @if($item->status === \App\Enums\ItemStatus::Sold && $item->soldItem)
                                @php
                                    $sellRoute = $this->shop
                                        ? route('shop.sells.show', [$this->shop, $item->soldItem->sell_id])
                                        : route('sells.show', $item->soldItem->sell_id);
                                @endphp
                                <flux:link href="{{ $sellRoute }}" wire:navigate>
                                    <flux:badge size="sm" :color="$item->status->color()">
                                        {{ $item->status->label() }}
                                    </flux:badge>
                                </flux:link>
                            @else
                                <flux:badge size="sm" :color="$item->status->color()">
                                    {{ $item->status->label() }}
                                </flux:badge>
                            @endif
                        </flux:table.cell>
                    @endif

                    @if(!$this->shop)
                        <flux:table.cell>
                            @if($item->shop ?? $item->parentShop ?? null)
                                @php $shop = $item->shop ?? $item->parentShop; @endphp
                                <flux:link
                                    href="{{ route('shop.items.index', $shop) }}"
                                    wire:navigate
                                    class="group/shop relative inline-flex items-center gap-1.5 overflow-hidden rounded-md px-2.5 py-1 text-xs font-medium w-[8rem] transition-colors duration-300"
                                    style="background: color-mix(in oklch, {{ $shop->color }} 15%, transparent);"
                                >
                                    <span
                                        class="absolute inset-0 origin-left scale-x-0 group-hover/shop:scale-x-100 transition-transform duration-300 ease-out"
                                        style="background: {{ $shop->color }};"
                                    ></span>
                                    <span
                                        class="relative size-2 shrink-0 rounded-full ring-1 ring-black/10 dark:ring-white/10"
                                        style="background: {{ $shop->color }};"
                                    ></span>
                                    <span class="relative truncate text-zinc-700 dark:text-zinc-200 group-hover/shop:text-white transition-colors duration-300">
                                        {{ $shop->short_name ? Str::limit($shop->short_name, 12) : Str::limit($shop->name, 12) }}
                                    </span>
                                </flux:link>
                            @endif
                        </flux:table.cell>
                    @endif

                </flux:table.row>
            @empty
                <flux:table.row>
                    {{-- Dynamic colspan: always-visible ID column + all active columns --}}
                    <flux:table.cell :colspan="count($columns) + 1" class="py-16">
                        <div class="flex flex-col items-center gap-3 text-zinc-400">
                            <flux:icon name="archive-box" class="size-10 opacity-30" />
                            <span class="text-sm">Brak itemów spełniających kryteria</span>
                            @if($search || $status !== 'all' || $category || $brand ?? false)
                                <flux:button
                                    wire:click="$set('search', ''); $set('status', '1'); $set('category', ''); $set('brand', '');"
                                    size="sm"
                                    variant="ghost"
                                >
                                    Wyczyść filtry
                                </flux:button>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>