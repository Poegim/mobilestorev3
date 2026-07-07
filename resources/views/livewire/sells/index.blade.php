<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Sprzedaż</flux:heading>
        <div class="flex items-center gap-3">
            <flux:select wire:model.live="perPage" class="w-20" size="sm">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="1000">1000</option>
                <option value="10000">10000</option>
            </flux:select>
            <flux:badge size="lg">{{ $sells->total() }} transakcji</flux:badge>
        </div>
    </div>

    {{-- Summary strip --}}
    @php $summary = $this->summary; @endphp
    <div class="mb-4 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm">
        <span class="font-semibold tabular-nums">
            Σ {{ number_format($summary['total'] / 100, 2, ',', ' ') }} zł
        </span>
        <span class="text-zinc-400">|</span>
        @foreach($summary['byMethod'] as $method)
            <span class="flex items-center gap-1 text-zinc-600 dark:text-zinc-400">
                {{ $method['label'] }}:
                <span class="font-medium text-zinc-900 dark:text-zinc-100 tabular-nums">{{ number_format($method['total'] / 100, 2, ',', ' ') }} zł</span>
                <span class="text-zinc-400 tabular-nums">({{ $method['count'] }})</span>
            </span>
        @endforeach
    </div>

    

    <div class="mb-4 flex flex-wrap gap-3">
        <flux:select wire:model.live="category" class="w-56">
            <option value="">Wszystkie kategorie</option>
            @foreach($this->categoryTree as $group)
                <optgroup label="{{ $group['name'] }}">
                    <option value="{{ $group['id'] }}">Wszystkie {{ $group['name'] }}</option>
                    @foreach($group['children'] as $child)
                        <option value="{{ $child['id'] }}">
                            {{ str_repeat('── ', $child['depth']) }}{{ $child['name'] }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </flux:select>
        <div class="flex-1 min-w-48">
            <flux:input wire:model.live.debounce.500ms="search" placeholder="ID sprzedaży, ID itemu, produkt lub IMEI..." icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="period" class="w-44">
            <option value="today">Dzisiaj</option>
            <option value="week">Ten tydzień</option>
            <option value="month">Ten miesiąc</option>
            <option value="year">Ten rok</option>
            <option value="custom">Zakres dat</option>
            <option value="all">Wszystko</option>
        </flux:select>
        <flux:select wire:model.live="paymentMethod" class="w-44">
            <option value="">Wszystkie metody</option>
            @foreach($this->paymentMethods as $pm)
                <option value="{{ $pm->value }}">{{ $pm->label() }}</option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="status" class="w-44">
            <option value="all">Wszystkie</option>
            <option value="valid">Aktywne</option>
            <option value="completed">Paragon</option>
            <option value="no_bill">Bez paragonu</option>
            <option value="cancelled">Anulowane</option>
        </flux:select>
    </div>

    @if($period === 'custom')
        <div class="mb-4 flex gap-3">
            <flux:input wire:model.live="dateFrom" type="date" label="Od" class="w-44" />
            <flux:input wire:model.live="dateTo" type="date" label="Do" class="w-44" />
        </div>
    @endif

    <flux:table :paginate="$sells">
        <flux:table.columns>
            <flux:table.column>ID</flux:table.column>
            <flux:table.column>Produkty</flux:table.column>
            <flux:table.column>Szt.</flux:table.column>
            <flux:table.column>Kwota</flux:table.column>
            <flux:table.column>Płatność</flux:table.column>
            <flux:table.column>Sklep</flux:table.column>
            <flux:table.column>Data</flux:table.column>
            <flux:table.column>Status</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($sells as $sell)
                @php $sellStatus = $sell->status(); @endphp
                <flux:table.row :class="$sellStatus === \App\Enums\SellStatus::Cancelled ? 'opacity-50 line-through' : ''">
                {{-- ID cell → link --}}
                <flux:table.cell variant="strong">
                    @php
                        $showRoute = $this->shop
                            ? route('shop.sells.show', [$this->shop, $sell])
                            : route('sells.show', $sell);
                    @endphp
                    <flux:link href="{{ $showRoute }}" wire:navigate>#{{ $sell->id }}</flux:link>
                </flux:table.cell>
                    <flux:table.cell>
                        @foreach($sell->soldItems as $si)
                            <div class="flex items-center justify-between gap-4 text-sm group leading-tight {{ !$loop->first ? 'mt-0.5' : '' }}">
                                <span class="flex items-center gap-1.5 min-w-0">
                                    @if($si->item?->product)
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ $si->item->product->brand->name ?? '' }} {{ $si->item->product->name }} 
                                                @if($si->item->feature_imei)
                                                <span class="font-mono text-xs text-green-500"> {{ $si->item->feature_imei }}</span>
                                                @endif 
                                            </span>
                                            
                                        </div>
                                        <button
                                            type="button"
                                            x-data="{ copied: false }"
                                            x-on:click="
                                                navigator.clipboard.writeText(@js($si->item->product->name));
                                                copied = true;
                                                setTimeout(() => copied = false, 1500)
                                            "
                                            class="cursor-pointer text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                                            title="Kopiuj nazwę"
                                        >
                                            <span x-show="!copied">
                                                <flux:icon.document-duplicate variant="micro" class="size-3.5" />
                                            </span>

                                            <span x-show="copied" x-cloak>
                                                <flux:icon.check variant="micro" class="size-3.5 text-emerald-500" />
                                            </span>
                                        </button>

                                        {{-- Low stock indicator --}}
                                        @php
                                            $stockKey = $si->item->product_id . ':' . $sell->parent_shop_id;
                                            $remaining = $stockMap[$stockKey] ?? null;
                                        @endphp
                                        @if($remaining !== null)
                                        <span @class([
                                            'inline-flex items-center justify-center size-5 rounded-full text-[10px] font-bold shrink-0',
                                            'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400' => $remaining === 0,
                                            'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400' => $remaining === 1,
                                            'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400' => $remaining > 1,
                                        ])>{{ $remaining }}</span>
                                    @endif
                                    @elseif($si->service_id)
                                        <span class="italic text-zinc-400">Usługa #{{ $si->service_id }}</span>
                                    @endif
                                </span>
                                <span class="text-zinc-500 tabular-nums whitespace-nowrap">
                                    {{ number_format($si->price / 100, 2, ',', ' ') }} zł
                                </span>
                            </div>
                        @endforeach
                    </flux:table.cell>
                    <flux:table.cell>{{ $sell->items_count }}</flux:table.cell>
                    <flux:table.cell>
                        {{ number_format(($sell->total_price ?? 0) / 100, 2, ',', ' ') }} zł
                    </flux:table.cell>
                    <flux:table.cell>{{ $sell->payment_method->label() }}</flux:table.cell>
                    <flux:table.cell>
                        @if($sell->shop)
                            <span class="flex items-center gap-1.5">
                                <span class="size-4 rounded-full" style="background-color: {{ $sell->shop->color }}"></span>
                                {{ Str::limit($sell->shop->name, 10) }}
                            </span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500 text-sm">
                        {{ $sell->created_at->format('d.m.Y H:i') }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <span title="{{ $sellStatus->label() }}">
                            <flux:icon :name="$sellStatus->icon()" variant="mini" @class([
                                'size-5',
                                'text-green-500' => $sellStatus === \App\Enums\SellStatus::Completed,
                                'text-amber-500' => $sellStatus === \App\Enums\SellStatus::NoBill,
                                'text-red-500'   => $sellStatus === \App\Enums\SellStatus::Cancelled,
                            ]) />
                        </span>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center text-zinc-500">
                        Brak sprzedaży w wybranym okresie.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>