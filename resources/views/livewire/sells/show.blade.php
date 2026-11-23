<div>
    @php $sellStatus = $sell->status(); @endphp
    @php $isCancelled = $sellStatus === \App\Enums\SellStatus::Cancelled; @endphp

    {{-- Header --}}
    <div class="mb-6 flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="$this->backUrl" wire:navigate />
        <flux:heading size="xl">Sprzedaż #{{ $sell->id }}</flux:heading>
        <flux:badge :color="$sellStatus->color()" :icon="$sellStatus->icon()">{{ $sellStatus->label() }}</flux:badge>

        <div class="ml-auto flex items-center gap-2">
            @if(!$isCancelled)
                <flux:button variant="danger" size="sm" icon="x-mark"
                             x-on:click="Flux.modal('confirm-cancel-sell').show()">
                    Anuluj sprzedaż
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Meta info cards row --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

        {{-- Payment method — colored square badge like in index --}}
        <flux:card class="flex items-center gap-3 !py-3">
            <span class="inline-flex items-center justify-center size-10 rounded-lg {{ $sell->payment_method->bgClass() }}">
                <flux:icon :name="$sell->payment_method->icon()" variant="mini" class="size-5 {{ $sell->payment_method->iconClass() }}" />
            </span>
            <div>
                <flux:subheading class="!text-xs">Płatność</flux:subheading>
                <flux:text class="font-medium">{{ $sell->payment_method->label() }}</flux:text>
            </div>
        </flux:card>

        {{-- Date --}}
        <flux:card class="flex items-center gap-3 !py-3">
            <span class="inline-flex items-center justify-center size-10 rounded-lg bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="calendar-days" variant="mini" class="size-5 text-zinc-500" />
            </span>
            <div>
                <flux:subheading class="!text-xs">Data</flux:subheading>
                <flux:text class="font-medium tabular-nums">
                    {{ $sell->created_at->format('d.m.Y') }}
                    <span class="text-zinc-400 text-xs">{{ $sell->created_at->format('H:i') }}</span>
                </flux:text>
            </div>
        </flux:card>

        {{-- Shop --}}
        <flux:card class="flex items-center gap-3 !py-3">
            @if($sell->shop)
                <span class="inline-flex items-center justify-center size-10 rounded-lg" style="background: color-mix(in oklch, {{ $sell->shop->color }} 20%, transparent);">
                    <flux:icon name="building-storefront" variant="mini" class="size-5" style="color: {{ $sell->shop->color }}" />
                </span>
                <div>
                    <flux:subheading class="!text-xs">Sklep</flux:subheading>
                    <flux:text class="font-medium">{{ $sell->shop->name }}</flux:text>
                </div>
            @else
                <span class="inline-flex items-center justify-center size-10 rounded-lg bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon name="building-storefront" variant="mini" class="size-5 text-zinc-400" />
                </span>
                <div>
                    <flux:subheading class="!text-xs">Sklep</flux:subheading>
                    <flux:text class="text-zinc-400">—</flux:text>
                </div>
            @endif
        </flux:card>

        {{-- Receipt / bill --}}
        <flux:card class="flex items-center gap-3 !py-3">
            @if($sell->bill_printed_at)
                <span class="inline-flex items-center justify-center size-10 rounded-lg bg-green-100 dark:bg-green-900/30">
                    <flux:icon name="check-circle" variant="mini" class="size-5 text-green-500" />
                </span>
                <div>
                    <flux:subheading class="!text-xs">Paragon</flux:subheading>
                    <flux:text class="font-medium tabular-nums">{{ $sell->bill_printed_at->format('d.m.Y H:i') }}</flux:text>
                </div>
            @else
                <span class="inline-flex items-center justify-center size-10 rounded-lg bg-amber-100 dark:bg-amber-900/30">
                    <flux:icon name="minus-circle" variant="mini" class="size-5 text-amber-500" />
                </span>
                <div>
                    <flux:subheading class="!text-xs">Paragon</flux:subheading>
                    <flux:text class="text-zinc-400">Nie wydrukowano</flux:text>
                </div>
            @endif
        </flux:card>
    </div>

    {{-- Totals summary strip --}}
    <div class="mb-4 flex flex-wrap items-center gap-x-6 gap-y-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 px-5 py-3">
        <div class="flex items-baseline gap-2">
            <span class="text-sm text-zinc-500 dark:text-zinc-400">Pozycji:</span>
            <span class="font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $this->validItems->count() }} szt.</span>
        </div>
        <span class="text-zinc-300 dark:text-zinc-600 select-none">|</span>
        <div class="flex items-baseline gap-2">
            <span class="text-sm text-zinc-500 dark:text-zinc-400">Netto:</span>
            <span class="font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">{{ number_format($this->totalSellNet / 100, 2, ',', ' ') }} zł</span>
        </div>
        <span class="text-zinc-300 dark:text-zinc-600 select-none">|</span>
        <div class="flex items-baseline gap-2">
            <span class="text-sm text-zinc-500 dark:text-zinc-400">Brutto:</span>
            <span class="font-bold tabular-nums text-lg text-zinc-900 dark:text-white">{{ number_format($this->totalSellGross / 100, 2, ',', ' ') }} zł</span>
        </div>

        @if($isAdmin)
            <span class="text-zinc-300 dark:text-zinc-600 select-none">|</span>
            <div class="flex items-baseline gap-2">
                <span class="text-sm text-zinc-500 dark:text-zinc-400">Zakup:</span>
                <span class="tabular-nums text-zinc-500">{{ number_format($this->totalPurchaseGross / 100, 2, ',', ' ') }} zł</span>
            </div>
            <span class="text-zinc-300 dark:text-zinc-600 select-none">|</span>
            <div class="flex items-baseline gap-2">
                <span class="text-sm text-zinc-500 dark:text-zinc-400">Dochód:</span>
                <span class="font-semibold tabular-nums {{ $this->totalIncome >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ number_format($this->totalIncome / 100, 2, ',', ' ') }} zł
                </span>
                @if($this->totalPurchaseGross > 0)
                    <span class="text-xs tabular-nums {{ $this->totalIncome >= 0 ? 'text-green-600/70' : 'text-red-600/70' }}">
                        ({{ number_format($this->totalIncome / $this->totalPurchaseGross * 100, 1, ',', '') }}%)
                    </span>
                @endif
            </div>
        @endif
    </div>

    {{-- Sold items table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-16">ID</flux:table.column>
            <flux:table.column>Produkt / Usługa</flux:table.column>
            <flux:table.column class="w-24">Stan</flux:table.column>
            <flux:table.column class="w-20">VAT</flux:table.column>
            <flux:table.column class="w-32 text-right">Netto</flux:table.column>
            <flux:table.column class="w-32 text-right">Brutto</flux:table.column>
            @if($isAdmin)
                <flux:table.column class="w-32 text-right">Zakup</flux:table.column>
                <flux:table.column class="w-32 text-right">Dochód</flux:table.column>
            @endif
        </flux:table.columns>

        <flux:table.rows>
            @forelse($sell->soldItems->sortByDesc('valid') as $si)
                <flux:table.row @class([
                    'opacity-50' => !$si->valid,
                ])>

                    {{-- Item ID or Service ID --}}
                    <flux:table.cell variant="strong" class="tabular-nums">
                        @if($si->item_id)
                            <flux:link :href="route($shop ? 'shop.items.index' : 'items.index', $shop ? [$shop] : []) . '?search=' . $si->item_id" wire:navigate class="font-mono">
                                #{{ $si->item_id }}
                            </flux:link>
                        @elseif($si->service_id)
                            <span class="text-purple-500 font-mono">S-{{ $si->service_id }}</span>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    {{-- Product or service name --}}
                    <flux:table.cell>
                        @if($si->item_id && $si->item?->product)
                            <div class="flex items-center gap-2">
                                <span @class(['font-medium', 'line-through' => !$si->valid])>
                                    {{ $si->item->product->brand?->name }}
                                    {{ $si->item->product->name }}
                                </span>
                                @if($si->item->feature_imei)
                                    <span class="font-mono text-xs text-emerald-600 dark:text-emerald-400">{{ $si->item->feature_imei }}</span>
                                @endif
                            </div>
                            @if($si->item->variant)
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $si->item->variant->display_name }}</span>
                            @endif
                        @elseif($si->service_id)
                            <span class="italic text-zinc-500">{{ $si->service?->name ?? "Usługa #{$si->service_id}" }}</span>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    {{-- Condition --}}
                    <flux:table.cell>
                        @if($si->item?->condition)
                            <flux:badge size="sm" color="zinc">{{ $si->item->condition->name }}</flux:badge>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    {{-- Tax rate --}}
                    <flux:table.cell>
                        @if($si->tax_id && $si->tax)
                            <flux:badge size="sm" color="zinc">{{ $si->tax->name }}</flux:badge>
                        @else
                            <flux:badge size="sm" color="amber">Marża</flux:badge>
                        @endif
                    </flux:table.cell>

                    {{-- Sell net --}}
                    <flux:table.cell class="tabular-nums whitespace-nowrap text-right">
                        {{ number_format($si->getNetPrice() / 100, 2, ',', ' ') }} zł
                    </flux:table.cell>

                    {{-- Sell gross --}}
                    <flux:table.cell class="tabular-nums whitespace-nowrap text-right font-medium">
                        {{ number_format($si->price / 100, 2, ',', ' ') }} zł
                    </flux:table.cell>

                    @if($isAdmin)
                        {{-- Purchase gross --}}
                        <flux:table.cell class="tabular-nums whitespace-nowrap text-right text-zinc-500">
                            @if($si->item_id && $si->item?->purchasedItem)
                                {{ number_format($si->item->purchasedItem->getGrossPrice() / 100, 2, ',', ' ') }} zł
                            @elseif($si->service_id)
                                {{ number_format(($si->internal_cost ?? 0) / 100, 2, ',', ' ') }} zł
                            @else
                                —
                            @endif
                        </flux:table.cell>

                        {{-- Income --}}
                        @php $income = $si->valid ? $si->getIncome() : 0; @endphp
                        <flux:table.cell class="tabular-nums whitespace-nowrap text-right font-medium {{ $income >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            @if($si->valid)
                                {{ number_format($income / 100, 2, ',', ' ') }} zł
                            @else
                                <span class="text-zinc-400 font-normal">—</span>
                            @endif
                        </flux:table.cell>
                    @endif

                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell :colspan="$isAdmin ? 8 : 6" class="text-center py-12">
                        <div class="flex flex-col items-center gap-2 text-zinc-400">
                            <flux:icon name="receipt-percent" class="size-8 opacity-30" />
                            <span class="text-sm">Brak pozycji</span>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse

            {{-- Totals row --}}
            @if($this->validItems->isNotEmpty())
                <flux:table.row class="border-t-2 border-zinc-300 dark:border-zinc-600 !bg-zinc-50 dark:!bg-zinc-800/60">
                    <flux:table.cell colspan="4" variant="strong" class="text-right">
                        Razem
                    </flux:table.cell>
                    <flux:table.cell variant="strong" class="tabular-nums whitespace-nowrap text-right">
                        {{ number_format($this->totalSellNet / 100, 2, ',', ' ') }} zł
                    </flux:table.cell>
                    <flux:table.cell variant="strong" class="tabular-nums whitespace-nowrap text-right">
                        {{ number_format($this->totalSellGross / 100, 2, ',', ' ') }} zł
                    </flux:table.cell>
                    @if($isAdmin)
                        <flux:table.cell variant="strong" class="tabular-nums whitespace-nowrap text-right text-zinc-500">
                            {{ number_format($this->totalPurchaseGross / 100, 2, ',', ' ') }} zł
                        </flux:table.cell>
                        <flux:table.cell variant="strong" class="tabular-nums whitespace-nowrap text-right font-semibold {{ $this->totalIncome >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($this->totalIncome / 100, 2, ',', ' ') }} zł
                        </flux:table.cell>
                    @endif
                </flux:table.row>
            @endif
        </flux:table.rows>
    </flux:table>

    {{-- Cancel sell confirmation modal --}}
    <flux:modal name="confirm-cancel-sell" class="max-w-md">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center size-10 rounded-full bg-red-100 dark:bg-red-900/30">
                    <flux:icon name="exclamation-triangle" variant="mini" class="size-5 text-red-500" />
                </span>
                <flux:heading size="lg">Anulować sprzedaż #{{ $sell->id }}?</flux:heading>
            </div>

            <flux:text>
                Ta operacja oznaczy sprzedaż jako anulowaną i zwróci
                <strong>{{ $this->validItems->count() }}</strong> {{ $this->validItems->count() === 1 ? 'pozycję' : 'pozycji' }} do magazynu.
                Operacja jest nieodwracalna.
            </flux:text>

            {{-- Items that will return to store --}}
            @if($this->validItems->isNotEmpty())
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-800 text-sm max-h-48 overflow-y-auto">
                    @foreach($this->validItems as $si)
                        <div class="flex items-center justify-between px-3 py-2">
                            <div class="flex items-center gap-2 min-w-0">
                                @if($si->item_id)
                                    <flux:icon.cube variant="micro" class="size-4 text-blue-500 shrink-0" />
                                @else
                                    <flux:icon.wrench-screwdriver variant="micro" class="size-4 text-purple-500 shrink-0" />
                                @endif
                                <span class="truncate text-zinc-900 dark:text-zinc-100">
                                    {{ $si->item?->product?->brand?->name }} {{ $si->item?->product?->name ?? $si->service?->name ?? '—' }}
                                </span>
                            </div>
                            <span class="tabular-nums text-zinc-500 shrink-0 ml-3">
                                {{ number_format($si->price / 100, 2, ',', ' ') }} zł
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Nie, zostaw</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" icon="x-mark" wire:click="cancel">
                    Tak, anuluj
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>