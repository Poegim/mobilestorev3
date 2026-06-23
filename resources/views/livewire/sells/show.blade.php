<div>
    {{-- Header --}}
    @php $sellStatus = $sell->status(); @endphp
    <div class="mb-6 flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="$this->backUrl" wire:navigate />
        <flux:heading size="xl">Sprzedaż #{{ $sell->id }}</flux:heading>
        <flux:badge :color="$sellStatus->color()">{{ $sellStatus->label() }}</flux:badge>
    </div>

    {{-- Meta info --}}
    <flux:card class="mb-6">
        <div class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3 lg:grid-cols-4">
            <div>
                <flux:subheading>Data</flux:subheading>
                <flux:text class="mt-1 font-medium">
                    {{ $sell->created_at->format('d.m.Y H:i') }}
                </flux:text>
            </div>

            <div>
                <flux:subheading>Sklep</flux:subheading>
                <flux:text class="mt-1">
                    @if($sell->shop)
                        <span class="inline-flex items-center gap-1.5">
                            <span class="size-3 rounded-full" style="background-color: {{ $sell->shop->color }}"></span>
                            {{ $sell->shop->name }}
                        </span>
                    @else
                        —
                    @endif
                </flux:text>
            </div>

            <div>
                <flux:subheading>Płatność</flux:subheading>
                <flux:text class="mt-1">
                    <span class="inline-flex items-center gap-1.5">
                        <flux:icon :name="$sell->payment_method->icon()" variant="mini" class="size-4" />
                        {{ $sell->payment_method->label() }}
                    </span>
                </flux:text>
            </div>

            <div>
                <flux:subheading>Paragon</flux:subheading>
                <flux:text class="mt-1">
                    @if($sell->bill_printed_at)
                        <span class="inline-flex items-center gap-1.5 text-green-600">
                            <flux:icon.check-circle variant="mini" class="size-4" />
                            {{ $sell->bill_printed_at->format('d.m.Y H:i') }}
                        </span>
                    @else
                        <span class="text-zinc-400">—</span>
                    @endif
                </flux:text>
            </div>

            <div>
                <flux:subheading>Pozycji</flux:subheading>
                <flux:text class="mt-1 font-medium">
                    {{ $this->validItems->count() }} szt.
                </flux:text>
            </div>

            <div>
                <flux:subheading>Sprzedaż netto</flux:subheading>
                <flux:text class="mt-1 tabular-nums font-medium">
                    {{ number_format($this->totalSellNet / 100, 2, ',', ' ') }} zł
                </flux:text>
            </div>

            <div>
                <flux:subheading>Sprzedaż brutto</flux:subheading>
                <flux:text class="mt-1 tabular-nums font-semibold text-lg">
                    {{ number_format($this->totalSellGross / 100, 2, ',', ' ') }} zł
                </flux:text>
            </div>

            @if($isAdmin)
                <div>
                    <flux:subheading>Zakup netto</flux:subheading>
                    <flux:text class="mt-1 tabular-nums text-zinc-500">
                        {{ number_format($this->totalPurchaseNet / 100, 2, ',', ' ') }} zł
                    </flux:text>
                </div>

                <div>
                    <flux:subheading>Zakup brutto</flux:subheading>
                    <flux:text class="mt-1 tabular-nums text-zinc-500">
                        {{ number_format($this->totalPurchaseGross / 100, 2, ',', ' ') }} zł
                    </flux:text>
                </div>

                <div>
                    <flux:subheading>Dochód</flux:subheading>
                    <flux:text class="mt-1 tabular-nums font-semibold {{ $this->totalIncome >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($this->totalIncome / 100, 2, ',', ' ') }} zł
                    </flux:text>
                </div>

                <div>
                    <flux:subheading>Marża</flux:subheading>
                    <flux:text class="mt-1 tabular-nums font-medium {{ $this->totalIncome >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        @if($this->totalPurchaseGross > 0)
                            {{ number_format($this->totalIncome / $this->totalPurchaseGross * 100, 1, ',', '') }}%
                        @else
                            —
                        @endif
                    </flux:text>
                </div>
            @endif
        </div>
    </flux:card>

    {{-- Sold items table --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Pozycje sprzedaży</flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>ID</flux:table.column>
                <flux:table.column>Produkt / Usługa</flux:table.column>
                <flux:table.column>Stan</flux:table.column>
                <flux:table.column>Faktura zakupu</flux:table.column>
                <flux:table.column>VAT</flux:table.column>
                <flux:table.column>Sprzedaż netto</flux:table.column>
                <flux:table.column>Sprzedaż brutto</flux:table.column>
                @if($isAdmin)
                    <flux:table.column>Zakup netto</flux:table.column>
                    <flux:table.column>Zakup brutto</flux:table.column>
                    <flux:table.column>Dochód</flux:table.column>
                @endif
            </flux:table.columns>

            <flux:table.rows>
                @forelse($sell->soldItems->sortByDesc('valid') as $si)
                    <flux:table.row :class="!$si->valid ? 'opacity-50 line-through' : ''">

                        {{-- Item ID or Service ID --}}
                        <flux:table.cell variant="strong">
                            @if($si->item_id)
                                {{ $si->item_id }}
                            @elseif($si->service_id)
                                S-{{ $si->service_id }}
                            @else
                                —
                            @endif
                        </flux:table.cell>

                        {{-- Product or service name --}}
                        <flux:table.cell>
                            @if($si->item_id && $si->item?->product)
                                <span class="font-medium">
                                    {{ $si->item->product->brand?->name }}
                                    {{ $si->item->product->name }}
                                </span>
                            @elseif($si->service_id)
                                <span class="italic text-zinc-500">Usługa #{{ $si->service_id }}</span>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>

                        {{-- Condition --}}
                        <flux:table.cell>
                            {{ $si->item?->condition?->name ?? '—' }}
                        </flux:table.cell>

                        {{-- Purchase invoice link --}}
                        <flux:table.cell>
                            @if($si->item_id && $si->item?->purchasedItem?->purchase)
                                @php $purchase = $si->item->purchasedItem->purchase; @endphp
                                {{-- TODO: replace href with route('purchases.show', $purchase) --}}
                                <flux:link href="#purchase-{{ $purchase->id }}" class="text-sm">
                                    {{ $purchase->invoice_number ?: 'Zakup #' . $purchase->id }}
                                </flux:link>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>

                        {{-- Tax rate --}}
                        <flux:table.cell>
                            @if($si->tax_id && $si->tax)
                                {{ $si->tax->name }}
                            @else
                                <span class="text-zinc-400">marża</span>
                            @endif
                        </flux:table.cell>

                        {{-- Sell net --}}
                        <flux:table.cell class="tabular-nums whitespace-nowrap">
                            {{ number_format($si->getNetPrice() / 100, 2, ',', ' ') }} zł
                        </flux:table.cell>

                        {{-- Sell gross --}}
                        <flux:table.cell class="tabular-nums whitespace-nowrap font-medium">
                            {{ number_format($si->price / 100, 2, ',', ' ') }} zł
                        </flux:table.cell>

                        @if($isAdmin)
                            {{-- Purchase net --}}
                            <flux:table.cell class="tabular-nums whitespace-nowrap text-zinc-500">
                                @if($si->item_id && $si->item?->purchasedItem)
                                    {{ number_format($si->item->purchasedItem->price / 100, 2, ',', ' ') }} zł
                                @elseif($si->service_id)
                                    {{ number_format($si->internal_cost / 100, 2, ',', ' ') }} zł
                                @else
                                    —
                                @endif
                            </flux:table.cell>

                            {{-- Purchase gross --}}
                            <flux:table.cell class="tabular-nums whitespace-nowrap text-zinc-500">
                                @if($si->item_id && $si->item?->purchasedItem)
                                    {{ number_format($si->item->purchasedItem->getGrossPrice() / 100, 2, ',', ' ') }} zł
                                @elseif($si->service_id)
                                    {{ number_format($si->internal_cost / 100, 2, ',', ' ') }} zł
                                @else
                                    —
                                @endif
                            </flux:table.cell>

                            {{-- Income --}}
                            @php $income = $si->valid ? $si->getIncome() : 0; @endphp
                            <flux:table.cell class="tabular-nums whitespace-nowrap font-medium {{ $income >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                @if($si->valid)
                                    {{ number_format($income / 100, 2, ',', ' ') }} zł
                                @else
                                    —
                                @endif
                            </flux:table.cell>
                        @endif

                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell :colspan="$isAdmin ? 10 : 7" class="text-center text-zinc-500">
                            Brak pozycji
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse

                {{-- Totals row --}}
                @if($this->validItems->isNotEmpty())
                    <flux:table.row class="border-t-2 border-zinc-300 dark:border-zinc-600 !bg-zinc-50 dark:!bg-zinc-800/60">
                        <flux:table.cell colspan="5" variant="strong" class="text-right">
                            Razem
                        </flux:table.cell>
                        <flux:table.cell variant="strong" class="tabular-nums whitespace-nowrap">
                            {{ number_format($this->totalSellNet / 100, 2, ',', ' ') }} zł
                        </flux:table.cell>
                        <flux:table.cell variant="strong" class="tabular-nums whitespace-nowrap">
                            {{ number_format($this->totalSellGross / 100, 2, ',', ' ') }} zł
                        </flux:table.cell>
                        @if($isAdmin)
                            <flux:table.cell variant="strong" class="tabular-nums whitespace-nowrap text-zinc-500">
                                {{ number_format($this->totalPurchaseNet / 100, 2, ',', ' ') }} zł
                            </flux:table.cell>
                            <flux:table.cell variant="strong" class="tabular-nums whitespace-nowrap text-zinc-500">
                                {{ number_format($this->totalPurchaseGross / 100, 2, ',', ' ') }} zł
                            </flux:table.cell>
                            <flux:table.cell variant="strong" class="tabular-nums whitespace-nowrap font-semibold {{ $this->totalIncome >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($this->totalIncome / 100, 2, ',', ' ') }} zł
                            </flux:table.cell>
                        @endif
                    </flux:table.row>
                @endif
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>