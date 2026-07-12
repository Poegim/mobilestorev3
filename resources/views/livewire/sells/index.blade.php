<div>
    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Sprzedaż</flux:heading>
        <div class="flex items-center gap-3">
            <flux:select wire:model.live="perPage" class="w-20" size="sm">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="1000">1000</option>
            </flux:select>
            <flux:badge size="lg" color="zinc">{{ $sells->total() }} transakcji</flux:badge>
        </div>
    </div>

    {{-- Summary strip — total + per-method breakdown --}}
    @php $summary = $this->summary; @endphp
    <div class="mb-4 flex flex-wrap items-center gap-x-4 gap-y-1 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 px-4 py-2.5 text-sm">
        <span class="font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
            Σ {{ number_format($summary['total'] / 100, 2, ',', ' ') }} zł
        </span>
        <span class="text-zinc-300 dark:text-zinc-600 select-none">|</span>
        @foreach($summary['byMethod'] as $method)
            @php $pm = \App\Enums\PaymentMethod::tryFrom(array_search($method, $summary['byMethod']) ?: 0); @endphp
            <span class="flex items-center gap-1.5 text-zinc-500 dark:text-zinc-400">
                <span class="font-medium text-zinc-700 dark:text-zinc-200 tabular-nums">
                    {{ number_format($method['total'] / 100, 2, ',', ' ') }} zł
                </span>
                <span class="text-zinc-400 text-xs">{{ $method['label'] }}</span>
                <span class="text-zinc-400 tabular-nums text-xs">({{ $method['count'] }})</span>
            </span>
        @endforeach
    </div>

    {{-- Toolbar --}}
    <div class="mb-4 flex flex-wrap gap-2 items-center">

        {{-- Search --}}
        <div class="flex-1 min-w-56">
            <flux:input
                wire:model.live.debounce.500ms="search"
                placeholder="ID sprzedaży, ID itemu, produkt lub IMEI..."
                icon="magnifying-glass"
                clearable
            />
        </div>

        <flux:separator vertical class="h-8 hidden sm:block" />

        {{-- Filters --}}
        <flux:select wire:model.live="category" class="w-52">
            <option value="">Wszystkie kategorie</option>
            @foreach($this->categoryTree as $group)
                <optgroup label="{{ $group['name'] }}">
                    <option value="{{ $group['id'] }}">Wszystkie {{ $group['name'] }}</option>
                    @foreach($group['children'] as $child)
                        <option value="{{ $child['id'] }}">{{ str_repeat('── ', $child['depth']) }}{{ $child['name'] }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="period" class="w-40">
            <option value="today">Dzisiaj</option>
            <option value="week">Ten tydzień</option>
            <option value="month">Ten miesiąc</option>
            <option value="year">Ten rok</option>
            <option value="custom">Zakres dat</option>
            <option value="all">Wszystko</option>
        </flux:select>

        <flux:select wire:model.live="paymentMethod" class="w-40">
            <option value="">Wszystkie metody</option>
            @foreach($this->paymentMethods as $pm)
                <option value="{{ $pm->value }}">{{ $pm->label() }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="status" class="w-40">
            <option value="all">Wszystkie</option>
            <option value="valid">Aktywne</option>
            <option value="completed">Paragon</option>
            <option value="no_bill">Bez paragonu</option>
            <option value="cancelled">Anulowane</option>
        </flux:select>
    </div>

    {{-- Custom date range picker --}}
    @if($period === 'custom')
        <div class="mb-4 flex gap-3">
            <flux:input wire:model.live="dateFrom" type="date" label="Od" class="w-44" />
            <flux:input wire:model.live="dateTo" type="date" label="Do" class="w-44" />
        </div>
    @endif

    {{-- Table --}}
    <flux:table :paginate="$sells">
        <flux:table.columns>
            <flux:table.column class="w-16">#</flux:table.column>
            <flux:table.column>Produkty</flux:table.column>
            <flux:table.column class="w-12 text-center">Szt.</flux:table.column>
            <flux:table.column class="w-32">Kwota</flux:table.column>
            <flux:table.column class="w-12 text-center">Płat.</flux:table.column>
            <flux:table.column class="w-36">Data</flux:table.column>
            <flux:table.column class="w-10 text-center">St.</flux:table.column>
            @if(!$this->shop)
                <flux:table.column class="w-32">Sklep</flux:table.column>
            @endif
        </flux:table.columns>

        <flux:table.rows>
            @forelse($sells as $sell)
                @php $sellStatus = $sell->status(); @endphp
                @php $isCancelled = $sellStatus === \App\Enums\SellStatus::Cancelled; @endphp

                {{--
                    Cancelled rows: subtle red background tint to signal invalidity at a glance.
                    Line-through applied only to the product name text, not the entire row,
                    so amounts, dates and icons remain fully readable.
                --}}
                <flux:table.row @class([
                    'relative',
                    'bg-red-50/60 dark:bg-red-950/20' => $isCancelled,
                ])>

                    {{-- ID — link to sell detail --}}
                    <flux:table.cell variant="strong" class="tabular-nums">
                        @php
                            $showRoute = $this->shop
                                ? route('shop.sells.show', [$this->shop, $sell])
                                : route('sells.show', $sell);
                        @endphp
                        <flux:link href="{{ $showRoute }}" wire:navigate class="font-mono">
                            #{{ $sell->id }}
                        </flux:link>
                    </flux:table.cell>

                    {{-- Products list --}}
                    <flux:table.cell>
                        @foreach($sell->soldItems as $si)
                            <div @class([
                                'flex items-center justify-between gap-3 text-sm leading-snug',
                                'mt-1 pt-1 border-t border-zinc-100 dark:border-zinc-800' => !$loop->first,
                            ])>
                                <span class="flex items-center gap-1.5 min-w-0">
                                    @if($si->item?->product)
                                        {{-- Product name: line-through only here for cancelled sells --}}
                                        <span @class([
                                            'font-medium truncate',
                                            'line-through text-zinc-400 dark:text-zinc-500' => $isCancelled,
                                        ])>
                                            {{ $si->item->product->brand->name ?? '' }}
                                            {{ $si->item->product->name }}
                                        </span>

                                        @if($si->item->feature_imei)
                                            <span class="font-mono text-xs text-emerald-600 dark:text-emerald-400 shrink-0">
                                                {{ $si->item->feature_imei }}
                                            </span>
                                        @endif

                                        {{-- Copy product name button --}}
                                        <button
                                            type="button"
                                            x-data="{ copied: false }"
                                            x-on:click="
                                                navigator.clipboard.writeText(@js($si->item->product->name));
                                                copied = true;
                                                setTimeout(() => copied = false, 1500)
                                            "
                                            class="shrink-0 cursor-pointer text-zinc-300 hover:text-zinc-500 dark:hover:text-zinc-300 transition-colors"
                                            title="Kopiuj nazwę"
                                        >
                                            <span x-show="!copied">
                                                <flux:icon.document-duplicate variant="micro" class="size-3.5" />
                                            </span>
                                            <span x-show="copied" x-cloak>
                                                <flux:icon.check variant="micro" class="size-3.5 text-emerald-500" />
                                            </span>
                                        </button>

                                        {{-- Remaining stock badge --}}
                                        @php
                                            $stockKey  = $si->item->product_id . ':' . $sell->parent_shop_id;
                                            $remaining = $stockMap[$stockKey] ?? null;
                                        @endphp
                                        @if($remaining !== null)
                                            <span @class([
                                                'inline-flex items-center justify-center size-5 rounded-full text-[10px] font-bold shrink-0',
                                                'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400'         => $remaining === 0,
                                                'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400' => $remaining === 1,
                                                'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400'        => $remaining > 1,
                                            ]) title="Pozostało na stanie: {{ $remaining }}">{{ $remaining }}</span>
                                        @endif

                                    @elseif($si->service_id)
                                        <span class="italic text-zinc-400">Usługa #{{ $si->service_id }}</span>
                                    @endif
                                </span>

                                {{-- Per-item price --}}
                                <span @class([
                                    'tabular-nums whitespace-nowrap shrink-0 text-xs',
                                    'text-zinc-300 dark:text-zinc-600 line-through' => $isCancelled,
                                    'text-zinc-500' => !$isCancelled,
                                ])>
                                    {{ number_format($si->price / 100, 2, ',', ' ') }} zł
                                </span>
                            </div>
                        @endforeach
                    </flux:table.cell>

                    {{-- Item count --}}
                    <flux:table.cell class="text-center tabular-nums text-zinc-500">
                        {{ $sell->items_count }}
                    </flux:table.cell>

                    {{-- Total amount — struck through + muted on cancelled --}}
                    <flux:table.cell @class([
                        'tabular-nums whitespace-nowrap',
                        'font-medium' => !$isCancelled,
                        'text-zinc-400 line-through' => $isCancelled,
                    ])>
                        {{ number_format(($sell->total_price ?? 0) / 100, 2, ',', ' ') }} zł
                    </flux:table.cell>

                    {{-- Payment method — colored square badge, icon only, tooltip on hover --}}
                    <flux:table.cell class="text-center">
                        <flux:tooltip :content="$sell->payment_method->label()">
                            <span class="inline-flex items-center justify-center size-8 rounded-md {{ $sell->payment_method->bgClass() }}">
                                <flux:icon
                                    :name="$sell->payment_method->icon()"
                                    variant="mini"
                                    class="size-5 {{ $sell->payment_method->iconClass() }}"
                                />
                            </span>
                        </flux:tooltip>
                    </flux:table.cell>

                    {{-- Date — time on separate line in smaller text --}}
                    <flux:table.cell class="text-zinc-500 text-sm tabular-nums whitespace-nowrap">
                        {{ $sell->created_at->format('d.m.Y') }}
                        <span class="text-zinc-400 text-xs block">{{ $sell->created_at->format('H:i:s') }}</span>
                    </flux:table.cell>

                    {{-- Status icon with tooltip --}}
                    <flux:table.cell class="text-center">
                        <span title="{{ $sellStatus->label() }}" class="inline-flex">
                            <flux:icon :name="$sellStatus->icon()" variant="mini" @class([
                                'size-5',
                                'text-green-500' => $sellStatus === \App\Enums\SellStatus::Completed,
                                'text-amber-500' => $sellStatus === \App\Enums\SellStatus::NoBill,
                                'text-red-500'   => $sellStatus === \App\Enums\SellStatus::Cancelled,
                            ]) />
                        </span>
                    </flux:table.cell>

                    
                    {{-- Shop badge with color tint --}}
                    @if(!$this->shop)
                        <flux:table.cell>
                            @if($sell->shop)
                                <flux:link
                                    href="{{ route('shop.sells.index', $sell->shop) }}"
                                    wire:navigate
                                    class="group/shop relative inline-flex items-center gap-1.5 overflow-hidden rounded-md px-2.5 py-1 text-xs font-medium w-[8rem] transition-colors duration-300"
                                    style="background: color-mix(in oklch, {{ $sell->shop->color }} 15%, transparent);"
                                >
                                    <span
                                        class="absolute inset-0 origin-left scale-x-0 group-hover/shop:scale-x-100 transition-transform duration-300 ease-out"
                                        style="background: {{ $sell->shop->color }};"
                                    ></span>
                                    <span
                                        class="relative size-2 shrink-0 rounded-full ring-1 ring-black/10 dark:ring-white/10"
                                        style="background: {{ $sell->shop->color }};"
                                    ></span>
                                    <span class="relative truncate text-zinc-700 dark:text-zinc-200 transition-colors duration-300">
                                        {{ $sell->shop->short_name ? Str::limit($sell->shop->short_name, 12) : Str::limit($sell->shop->name, 12) }}
                                    </span>
                                </flux:link>
                            @endif
                        </flux:table.cell>
                    @endif


                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="py-16">
                        <div class="flex flex-col items-center gap-3 text-zinc-400">
                            <flux:icon name="receipt-percent" class="size-10 opacity-30" />
                            <span class="text-sm">Brak sprzedaży w wybranym okresie</span>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>