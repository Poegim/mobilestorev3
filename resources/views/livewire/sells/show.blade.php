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
                {{-- Print receipt button (disabled when total is 0) --}}
                @if($this->totalSellGross > 0)
                    <flux:button variant="primary" size="sm" icon="printer"
                                 x-on:click="Flux.modal('print-receipt').show()">
                        Drukuj paragon
                    </flux:button>
                @else
                    <flux:button variant="primary" size="sm" icon="printer" disabled
                                 title="Nie można wydrukować paragonu na kwotę 0 zł">
                        Drukuj paragon
                    </flux:button>
                @endif

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

    {{-- Print receipt modal --}}
    <flux:modal name="print-receipt" class="max-w-md">
        <div x-data="{
            nipMode: 'no',
            nipInput: '',
            printing: false,
            error: '',

            isValidNip(nip) {
                if (typeof nip !== 'string') return false;
                nip = nip.replace(/[\s\-]/g, '');
                if (nip.length !== 10 || !/^\d{10}$/.test(nip)) return false;

                const weights = [6, 5, 7, 2, 3, 4, 5, 6, 7];
                let sum = 0;
                for (let i = 0; i < 9; i++) {
                    sum += parseInt(nip[i]) * weights[i];
                }
                return (sum % 11) === parseInt(nip[9]);
            },

            async printReceipt() {
                this.error = '';

                // Validate: receipt must have positions and total > 0
                const payload = @js($this->printerPayload);
                if (!payload.pos || payload.pos.length === 0) {
                    this.error = 'Brak pozycji do wydrukowania — wszystkie mają cenę 0 zł.';
                    return;
                }
                if (!payload.pay || payload.pay.c1 <= 0) {
                    this.error = 'Nie można wydrukować paragonu na kwotę 0 zł.';
                    return;
                }

                if (this.nipMode === 'yes') {
                    if (!this.nipInput.trim()) {
                        this.error = 'Podaj numer NIP.';
                        return;
                    }
                    if (!this.isValidNip(this.nipInput.trim())) {
                        this.error = 'Nieprawidłowy NIP. Sprawdź numer.';
                        return;
                    }
                }

                this.printing = true;

                try {
                    if (this.nipMode === 'yes') {
                        payload.nipNumber = this.nipInput.replace(/[\s\-]/g, '');
                    }

                    const ws = new WebSocket('wss://localhost:8000');

                    await new Promise((resolve, reject) => {
                        ws.onopen = () => {
                            ws.send(JSON.stringify(payload));
                            ws.close();
                            resolve();
                        };
                        ws.onerror = () => {
                            reject(new Error('Brak połączenia z programem drukarki. Upewnij się, że program jest uruchomiony.'));
                        };
                    });

                    await $wire.markBillPrinted();

                    Flux.modal('print-receipt').close();
                    this.nipMode = 'no';
                    this.nipInput = '';

                } catch (e) {
                    this.error = e.message || 'Wystąpił błąd połączenia z drukarką.';
                } finally {
                    this.printing = false;
                }
            }
        }" class="space-y-4">

            {{-- Header --}}
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center size-10 rounded-full bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon name="printer" variant="mini" class="size-5 text-blue-500" />
                </span>
                <flux:heading size="lg">Drukuj paragon #{{ $sell->id }}</flux:heading>
            </div>

            {{-- Warning: already printed --}}
            @if($this->isBillPrinted)
                <div class="flex items-start gap-2 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3">
                    <flux:icon name="exclamation-triangle" variant="mini" class="size-5 text-amber-500 shrink-0 mt-0.5" />
                    <div class="text-sm text-amber-800 dark:text-amber-200">
                        <p class="font-medium">Paragon już był drukowany</p>
                        <p class="text-amber-600 dark:text-amber-400">{{ $sell->bill_printed_at->format('d.m.Y H:i') }} — ponowne drukowanie wyśle duplikat do drukarki.</p>
                    </div>
                </div>
            @endif

            {{-- Summary --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 space-y-1">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400">Pozycji na paragonie:</span>
                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $this->validItems->count() }} szt.</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400">Metoda płatności:</span>
                    <span class="inline-flex items-center gap-1.5 font-medium text-zinc-900 dark:text-zinc-100">
                        <span class="inline-flex items-center justify-center size-5 rounded {{ $sell->payment_method->bgClass() }}">
                            <flux:icon :name="$sell->payment_method->icon()" variant="micro" class="size-3 {{ $sell->payment_method->iconClass() }}" />
                        </span>
                        {{ $sell->payment_method->label() }}
                    </span>
                </div>
                <div class="flex items-center justify-between pt-1 border-t border-zinc-100 dark:border-zinc-700">
                    <span class="font-medium text-zinc-900 dark:text-zinc-100">Do zapłaty:</span>
                    <span class="font-bold text-lg tabular-nums text-zinc-900 dark:text-white">{{ number_format($this->totalSellGross / 100, 2, ',', ' ') }} zł</span>
                </div>
            </div>

            {{-- NIP option --}}
            <div class="space-y-3">
                <flux:subheading>NIP na paragonie</flux:subheading>
                <div class="flex items-center gap-4">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="nip_mode" x-model="nipMode" value="no"
                               class="text-blue-500 focus:ring-blue-500 dark:bg-zinc-700 dark:border-zinc-600" />
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">Bez NIP</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="nip_mode" x-model="nipMode" value="yes"
                               class="text-blue-500 focus:ring-blue-500 dark:bg-zinc-700 dark:border-zinc-600" />
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">Z NIP-em</span>
                    </label>
                </div>

                <div x-show="nipMode === 'yes'" x-transition.duration.200ms>
                    <input type="text" x-model="nipInput" placeholder="Wpisz NIP (10 cyfr)" maxlength="13"
                           class="w-full rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                </div>
            </div>

            {{-- Error message --}}
            <div x-show="error" x-transition.duration.200ms
                 class="flex items-start gap-2 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3">
                <flux:icon name="x-circle" variant="mini" class="size-5 text-red-500 shrink-0 mt-0.5" />
                <span class="text-sm text-red-700 dark:text-red-300" x-text="error"></span>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Anuluj</flux:button>
                </flux:modal.close>
                <button type="button"
                        x-on:click="printReceipt()"
                        x-bind:disabled="printing"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed dark:focus:ring-offset-zinc-900">
                    <flux:icon name="printer" variant="mini" class="size-4" />
                    <span x-text="printing ? 'Drukuję...' : '{{ $this->isBillPrinted ? 'Drukuj ponownie' : 'Drukuj paragon' }}'"></span>
                </button>
            </div>
        </div>
    </flux:modal>

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