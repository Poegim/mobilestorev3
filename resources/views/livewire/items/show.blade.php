{{-- touch resources/views/livewire/items/show.blade.php --}}
@php
    $pi = $item->purchasedItem;
    $purchase = $pi?->purchase;
    $sellingPrice = $item->getSellingPrice();
@endphp

<div>
    {{-- Header --}}
    <div class="mb-4 flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left"
            :href="$shop ? route('shop.items.index', $shop) : route('items.index')"
            wire:navigate />
        <flux:heading size="xl">Item #{{ $item->id }}</flux:heading>
        <flux:badge size="sm" :color="$item->status->color()">{{ $item->status->label() }}</flux:badge>
    </div>

    {{-- Main info --}}
    <flux:card class="mb-6">
        <div class="grid grid-cols-2 gap-6 lg:grid-cols-4">
            <div>
                <flux:subheading>Produkt</flux:subheading>
                <flux:heading class="mt-1">{{ $item->product->brand->name ?? '' }} {{ $item->product->name }}</flux:heading>
            </div>

            <div>
                <flux:subheading>Kategoria</flux:subheading>
                <flux:heading class="mt-1">{{ $item->product->category->name ?? '—' }}</flux:heading>
            </div>

            @if($item->variant)
                <div>
                    <flux:subheading>Wariant</flux:subheading>
                    <flux:heading class="mt-1">{{ $item->variant->display_name }}</flux:heading>
                </div>
            @endif

            @if($item->color)
                <div>
                    <flux:subheading>Kolor</flux:subheading>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="inline-block size-3 rounded-full ring-1 ring-black/10 dark:ring-white/10"
                            style="background: {{ $item->color->color }};"></span>
                        <flux:heading>{{ $item->color->name }}</flux:heading>
                    </div>
                </div>
            @endif

            @if($item->condition)
                <div>
                    <flux:subheading>Stan</flux:subheading>
                    <flux:heading class="mt-1">{{ $item->condition->name }}</flux:heading>
                </div>
            @endif

            <div>
                <flux:subheading>Sklep</flux:subheading>
                @if($item->shop)
                    <div class="mt-1 flex items-center gap-2">
                        <span class="inline-block size-3 rounded-full ring-1 ring-black/10 dark:ring-white/10"
                            style="background: {{ $item->shop->color }};"></span>
                        <flux:heading>{{ $item->shop->short_name ?: $item->shop->name }}</flux:heading>
                    </div>
                @else
                    <flux:heading class="mt-1">—</flux:heading>
                @endif
            </div>

            @if($item->feature_imei)
                <div>
                    <flux:subheading>IMEI</flux:subheading>
                    <flux:heading class="mt-1">
                        <button
                            x-data
                            x-on:click="navigator.clipboard.writeText('{{ $item->feature_imei }}'); $flux.toast({ text: 'Skopiowano IMEI', variant: 'success' });"
                            class="cursor-copy font-mono tabular-nums hover:opacity-70 transition-opacity"
                            title="Kliknij, aby skopiować"
                        >{{ $item->feature_imei }}</button>
                    </flux:heading>
                </div>
            @endif

            @if($item->feature_memory)
                <div>
                    <flux:subheading>RAM</flux:subheading>
                    <flux:heading class="mt-1 tabular-nums">{{ $item->feature_memory }} GB</flux:heading>
                </div>
            @endif

            @if($item->feature_storage)
                <div>
                    <flux:subheading>Pamięć</flux:subheading>
                    <flux:heading class="mt-1 tabular-nums">{{ $item->feature_storage }} GB</flux:heading>
                </div>
            @endif

            @if($item->feature_box !== null)
                <div>
                    <flux:subheading>Pudełko</flux:subheading>
                    <div class="mt-1">
                        <flux:badge size="sm" :color="$item->feature_box ? 'green' : 'zinc'">
                            {{ $item->feature_box ? 'Tak' : 'Nie' }}
                        </flux:badge>
                    </div>
                </div>
            @endif

            @if($item->feature_proof_of_purchase !== null)
                <div>
                    <flux:subheading>Dowód zakupu</flux:subheading>
                    <div class="mt-1">
                        <flux:badge size="sm" :color="$item->feature_proof_of_purchase ? 'green' : 'zinc'">
                            {{ $item->feature_proof_of_purchase ? 'Tak' : 'Nie' }}
                        </flux:badge>
                    </div>
                </div>
            @endif
        </div>

        {{-- Comment displayed full-width below the grid --}}
        @if($item->feature_comment)
            <div class="mt-6 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:subheading>Komentarz / opis</flux:subheading>
                <flux:heading class="mt-1 whitespace-pre-line">{{ $item->feature_comment }}</flux:heading>
            </div>
        @endif
    </flux:card>

    {{-- Prices + purchase summary --}}
    <flux:card class="mb-6">
        <div class="grid grid-cols-2 gap-6 lg:grid-cols-4">
            @if($pi)
                <div>
                    <flux:subheading>Cena zakupu (netto)</flux:subheading>
                    <flux:heading class="mt-1 tabular-nums">{{ number_format($pi->price / 100, 2, ',', ' ') }} zł</flux:heading>
                </div>
                <div>
                    <flux:subheading>Cena zakupu (brutto)</flux:subheading>
                    <flux:heading class="mt-1 tabular-nums">{{ number_format($pi->getGrossPrice() / 100, 2, ',', ' ') }} zł</flux:heading>
                </div>
            @endif

            <div>
                <flux:subheading>Cena sprzedaży</flux:subheading>
                <flux:heading class="mt-1 tabular-nums">
                    {{ $sellingPrice !== null ? number_format($sellingPrice / 100, 2, ',', ' ') . ' zł' : '—' }}
                </flux:heading>
            </div>

            @if($pi && $sellingPrice)
                @php $margin = $sellingPrice - $pi->getGrossPrice(); @endphp
                <div>
                    <flux:subheading>Marża</flux:subheading>
                    <flux:heading class="mt-1 tabular-nums">{{ number_format($margin / 100, 2, ',', ' ') }} zł</flux:heading>
                </div>
            @endif

            @if($purchase)
                <div>
                    <flux:subheading>Nr zakupu</flux:subheading>
                    <flux:link
                        :href="$shop ? route('shop.purchases.show', [$shop, $purchase]) : route('purchases.show', $purchase)"
                        wire:navigate
                        class="mt-1 font-mono"
                    >#{{ $purchase->id }}</flux:link>
                </div>

                @if($purchase->invoice_number)
                    <div>
                        <flux:subheading>Nr faktury</flux:subheading>
                        <flux:heading class="mt-1 font-mono">{{ $purchase->invoice_number }}</flux:heading>
                    </div>
                @endif

                <div>
                    <flux:subheading>Dostawca</flux:subheading>
                    <flux:heading class="mt-1">{{ $purchase->contact->name ?? '—' }}</flux:heading>
                </div>

                <div>
                    <flux:subheading>Płatność</flux:subheading>
                    <flux:heading class="mt-1">{{ $purchase->payment_method->label() }}</flux:heading>
                </div>

                <div>
                    <flux:subheading>Data zakupu</flux:subheading>
                    <flux:heading class="mt-1">{{ $purchase->created_at->format('d.m.Y H:i') }}</flux:heading>
                </div>

                @if($purchase->shop)
                    <div>
                        <flux:subheading>Sklep przyjmujący</flux:subheading>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="inline-block size-3 rounded-full ring-1 ring-black/10 dark:ring-white/10"
                                style="background: {{ $purchase->shop->color }};"></span>
                            <flux:heading>{{ $purchase->shop->short_name ?: $purchase->shop->name }}</flux:heading>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </flux:card>

    {{-- Sale info --}}
    @if($item->soldItems->isNotEmpty())
        <flux:heading size="lg" class="mb-3">Sprzedaż</flux:heading>
        <flux:table class="mb-6">
            <flux:table.columns>
                <flux:table.column>#</flux:table.column>
                <flux:table.column>Cena</flux:table.column>
                <flux:table.column>Data</flux:table.column>
                <flux:table.column>Status</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($item->soldItems->sortByDesc('id') as $si)
                    <flux:table.row wire:key="si-{{ $si->id }}" @class(['opacity-50' => !$si->valid])>
                        <flux:table.cell>
                            <flux:link
                                :href="$shop ? route('shop.sells.show', [$shop, $si->sell_id]) : route('sells.show', $si->sell_id)"
                                wire:navigate
                                class="font-mono text-sm"
                            >#{{ $si->sell_id }}</flux:link>
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums whitespace-nowrap font-medium">
                            {{ number_format($si->price / 100, 2, ',', ' ') }} zł
                        </flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500">
                            {{ $si->sell?->created_at?->format('d.m.Y H:i') ?? '—' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($si->valid)
                                <flux:badge size="sm" color="green">Zrealizowana</flux:badge>
                            @else
                                <flux:badge size="sm" color="red">Anulowana</flux:badge>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    {{-- Transfer history --}}
    @if($item->transfers->isNotEmpty())
        <flux:heading size="lg" class="mb-3">Transfery</flux:heading>
        <flux:table class="mb-6">
            <flux:table.columns>
                <flux:table.column>#</flux:table.column>
                <flux:table.column>Skąd</flux:table.column>
                <flux:table.column>Dokąd</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Data</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($item->transfers->sortByDesc('created_at') as $transfer)
                    <flux:table.row wire:key="tr-{{ $transfer->id }}">
                        <flux:table.cell>
                            <flux:link
                                :href="$shop ? route('shop.transfers.show', [$shop, $transfer]) : route('transfers.show', $transfer)"
                                wire:navigate
                                class="font-mono text-sm"
                            >{{ $transfer->id }}</flux:link>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-1.5">
                                <span class="inline-block size-2 rounded-full"
                                    style="background: {{ $transfer->sourceShop->color ?? '#999' }};"></span>
                                {{ $transfer->sourceShop->short_name ?? $transfer->sourceShop->name ?? '—' }}
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-1.5">
                                <span class="inline-block size-2 rounded-full"
                                    style="background: {{ $transfer->targetShop->color ?? '#999' }};"></span>
                                {{ $transfer->targetShop->short_name ?? $transfer->targetShop->name ?? '—' }}
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$transfer->status->color()">{{ $transfer->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500">
                            {{ $transfer->created_at->format('d.m.Y H:i') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    {{-- Technical data --}}
    <flux:card>
        <div class="grid grid-cols-2 gap-6 lg:grid-cols-4">
            <div>
                <flux:subheading>Kod kreskowy</flux:subheading>
                <flux:heading class="mt-1">
                    <button
                        x-data
                        x-on:click="navigator.clipboard.writeText('{{ $item->getBarcode() }}'); $flux.toast({ text: 'Skopiowano kod', variant: 'success' });"
                        class="cursor-copy font-mono tabular-nums hover:opacity-70 transition-opacity"
                        title="Kliknij, aby skopiować"
                    >{{ $item->getBarcode() }}</button>
                </flux:heading>
            </div>

            @if($item->displaced_at)
                <div>
                    <flux:subheading>Na półce od</flux:subheading>
                    <flux:heading class="mt-1">{{ $item->displaced_at->format('d.m.Y') }}</flux:heading>
                    <flux:text size="sm">{{ $item->days_on_shelf }} dni</flux:text>
                </div>
            @endif

            @if($item->barcode_scanned_at)
                <div>
                    <flux:subheading>Ostatnie skanowanie</flux:subheading>
                    <flux:heading class="mt-1">{{ $item->barcode_scanned_at->format('d.m.Y H:i') }}</flux:heading>
                </div>
            @endif

            <div>
                <flux:subheading>Utworzony</flux:subheading>
                <flux:heading class="mt-1">{{ $item->created_at?->format('d.m.Y H:i') ?? '—' }}</flux:heading>
            </div>
        </div>
    </flux:card>
</div>