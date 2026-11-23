<div>
    {{-- Header --}}
    <div class="mb-6 flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('shop.sells.index', $shop)" wire:navigate />
        <flux:heading size="xl">Nowa sprzedaż</flux:heading>
        @if($shop)
            <flux:badge color="zinc" size="lg">
                <span class="inline-flex items-center gap-1.5">
                    <span class="size-2 rounded-full" style="background: {{ $shop->color }}"></span>
                    {{ $shop->name }}
                </span>
            </flux:badge>
        @endif
    </div>

    {{-- Payment method — colored square badges like in index --}}
    <div class="mb-4 flex flex-wrap items-center gap-x-4 gap-y-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 px-4 py-2.5">
        <span class="text-sm text-zinc-500 dark:text-zinc-400">Płatność:</span>
        <div class="flex gap-2">
            @foreach($this->paymentMethods as $pm)
                @php $isActive = $paymentMethod === $pm->value; @endphp
                <button
                    type="button"
                    wire:click="$set('paymentMethod', {{ $pm->value }})"
                    @class([
                        'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150',
                        'ring-2 ring-offset-1 ring-offset-white dark:ring-offset-zinc-900 shadow-sm' => $isActive,
                        'hover:bg-zinc-100 dark:hover:bg-zinc-700/50' => !$isActive,
                    ])
                    @if($isActive) style="
                        background: color-mix(in oklch, {{ match($pm) {
                            \App\Enums\PaymentMethod::Cash     => '#10b981',
                            \App\Enums\PaymentMethod::Card     => '#3b82f6',
                            \App\Enums\PaymentMethod::Transfer => '#8b5cf6',
                            \App\Enums\PaymentMethod::Allegro  => '#f97316',
                        } }} 15%, transparent);
                        --tw-ring-color: {{ match($pm) {
                            \App\Enums\PaymentMethod::Cash     => '#10b981',
                            \App\Enums\PaymentMethod::Card     => '#3b82f6',
                            \App\Enums\PaymentMethod::Transfer => '#8b5cf6',
                            \App\Enums\PaymentMethod::Allegro  => '#f97316',
                        } }};
                    " @endif
                >
                    <span @class([
                        'inline-flex items-center justify-center size-7 rounded-md',
                        $pm->bgClass(),
                    ])>
                        <flux:icon :name="$pm->icon()" variant="mini" class="size-4 {{ $pm->iconClass() }}" />
                    </span>
                    <span @class([
                        'text-zinc-900 dark:text-zinc-100' => $isActive,
                        'text-zinc-500 dark:text-zinc-400' => !$isActive,
                    ])>{{ $pm->label() }}</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Search sections side by side --}}
    <div class="grid gap-4 lg:grid-cols-2 mb-4">

        {{-- Item search --}}
        <flux:card>
            <div class="flex items-center gap-2 mb-3">
                <span class="inline-flex items-center justify-center size-7 rounded-md bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon.cube variant="micro" class="size-4 text-blue-500" />
                </span>
                <flux:heading size="sm">Dodaj przedmiot</flux:heading>
            </div>

            <div class="space-y-3">
                <flux:input
                    wire:model.live.debounce.300ms="itemSearch"
                    placeholder="ID, kod kreskowy, IMEI lub nazwa..."
                    icon="magnifying-glass"
                    clearable
                />

                @if($this->itemResults->isNotEmpty())
                    <div class="max-h-64 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($this->itemResults as $item)
                            <button
                                type="button"
                                wire:click="addItemToCart({{ $item->id }})"
                                class="w-full px-3 py-2.5 text-left text-sm flex items-center gap-2 hover:bg-blue-50 dark:hover:bg-blue-950/30 transition-colors"
                            >
                                <span class="text-zinc-400 text-xs tabular-nums font-mono w-10 shrink-0">#{{ $item->id }}</span>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100 truncate">
                                    {{ $item->product?->display_name ?? '—' }}
                                </span>
                                @if($item->variant)
                                    <span class="text-zinc-500 dark:text-zinc-400 text-xs truncate">{{ $item->variant->display_name }}</span>
                                @endif
                                @if($item->feature_imei)
                                    <span class="font-mono text-xs text-emerald-600 dark:text-emerald-400 ml-auto shrink-0">{{ $item->feature_imei }}</span>
                                @endif
                                @if($item->condition)
                                    <flux:badge size="sm" color="zinc">{{ $item->condition->name }}</flux:badge>
                                @endif
                                @php $price = $item->getSellingPrice(); @endphp
                                @if($price)
                                    <span class="tabular-nums text-xs font-medium text-zinc-700 dark:text-zinc-300 shrink-0">{{ number_format($price / 100, 2, ',', ' ') }} zł</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @elseif(strlen(trim($itemSearch)) >= 1 && $this->itemResults->isEmpty())
                    <div class="text-sm text-zinc-400 text-center py-4">Nie znaleziono przedmiotów</div>
                @endif

                @error('itemSearch') <p class="text-sm text-red-500">{{ $message }}</p> @enderror
            </div>
        </flux:card>

        {{-- Service search --}}
        <flux:card>
            <div class="flex items-center gap-2 mb-3">
                <span class="inline-flex items-center justify-center size-7 rounded-md bg-purple-100 dark:bg-purple-900/30">
                    <flux:icon.wrench-screwdriver variant="micro" class="size-4 text-purple-500" />
                </span>
                <flux:heading size="sm">Dodaj usługę</flux:heading>
            </div>

            <div class="space-y-3">
                <flux:input
                    wire:model.live.debounce.300ms="serviceSearch"
                    placeholder="Szukaj usługi..."
                    icon="magnifying-glass"
                    clearable
                />

                @if($this->serviceResults->isNotEmpty())
                    <div class="max-h-64 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($this->serviceResults as $service)
                            <button
                                type="button"
                                wire:click="addServiceToCart({{ $service->id }})"
                                class="w-full px-3 py-2.5 text-left text-sm flex items-center gap-3 hover:bg-purple-50 dark:hover:bg-purple-950/30 transition-colors"
                            >
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $service->name }}</span>
                                <span class="tabular-nums font-medium text-zinc-700 dark:text-zinc-300 ml-auto">{{ number_format($service->price / 100, 2, ',', ' ') }} zł</span>
                                @if($service->tax)
                                    <flux:badge size="sm" color="zinc">{{ $service->tax->name }}</flux:badge>
                                @else
                                    <flux:badge size="sm" color="amber">Marża</flux:badge>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @elseif(strlen(trim($serviceSearch)) >= 2 && $this->serviceResults->isEmpty())
                    <div class="text-sm text-zinc-400 text-center py-4">Nie znaleziono usług</div>
                @endif
            </div>
        </flux:card>
    </div>

    {{-- Cart --}}
    <flux:card>
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <flux:icon name="shopping-cart" class="size-5 text-zinc-400" />
                <flux:heading size="sm">Koszyk</flux:heading>
            </div>
            @if(!empty($cart))
                <flux:badge size="sm" color="blue">{{ count($cart) }} {{ count($cart) === 1 ? 'pozycja' : 'pozycji' }}</flux:badge>
            @endif
        </div>

        @if(!empty($cart))
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="w-24">Typ</flux:table.column>
                    <flux:table.column>Nazwa</flux:table.column>
                    <flux:table.column class="w-40">Szczegóły</flux:table.column>
                    <flux:table.column class="w-28">VAT</flux:table.column>
                    <flux:table.column class="w-32 text-right">Cena brutto</flux:table.column>
                    <flux:table.column class="w-10"></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($cart as $index => $entry)
                        <flux:table.row>
                            <flux:table.cell>
                                @if($entry['type'] === 'item')
                                    <span class="inline-flex items-center gap-1.5">
                                        <flux:icon.cube variant="micro" class="size-4 text-blue-500" />
                                        <span class="text-xs text-zinc-500">Przedmiot</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5">
                                        <flux:icon.wrench-screwdriver variant="micro" class="size-4 text-purple-500" />
                                        <span class="text-xs text-zinc-500">Usługa</span>
                                    </span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell variant="strong">
                                {{ $entry['name'] }}
                                @if($entry['detail'])
                                    <span class="text-zinc-500 dark:text-zinc-400 text-xs block">{{ $entry['detail'] }}</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-sm">
                                @if($entry['imei'])
                                    <span class="font-mono text-xs text-emerald-600 dark:text-emerald-400">{{ $entry['imei'] }}</span>
                                @endif
                                @if($entry['condition'])
                                    <flux:badge size="sm" color="zinc">{{ $entry['condition'] }}</flux:badge>
                                @endif
                                @if(!$entry['imei'] && !$entry['condition'])
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:select
                                    wire:change="updateCartTax({{ $index }}, $event.target.value)"
                                    size="sm"
                                    class="w-24"
                                >
                                    <option value="0" @selected($entry['tax_id'] == 0)>Marża</option>
                                    @foreach($this->taxes as $tax)
                                        <option value="{{ $tax->id }}" @selected($entry['tax_id'] == $tax->id)>{{ $tax->name }}</option>
                                    @endforeach
                                </flux:select>
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value="{{ number_format($entry['price'] / 100, 2, '.', '') }}"
                                    wire:change="updateCartPrice({{ $index }}, $event.target.value)"
                                    size="sm"
                                    class="w-28 text-right tabular-nums"
                                />
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button wire:click="removeFromCart({{ $index }})" variant="ghost" size="sm" icon="trash" class="text-red-500 hover:text-red-600" />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            {{-- Totals and confirm --}}
            <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-6">
                    <div class="flex items-baseline gap-2">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">Razem brutto:</span>
                        <span class="font-bold tabular-nums text-lg text-zinc-900 dark:text-white">
                            {{ number_format($this->cartTotal / 100, 2, ',', ' ') }} zł
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">Płatność:</span>
                        @php $activePm = \App\Enums\PaymentMethod::from($paymentMethod); @endphp
                        <span class="inline-flex items-center gap-1.5">
                            <span class="inline-flex items-center justify-center size-6 rounded-md {{ $activePm->bgClass() }}">
                                <flux:icon :name="$activePm->icon()" variant="micro" class="size-3.5 {{ $activePm->iconClass() }}" />
                            </span>
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $activePm->label() }}</span>
                        </span>
                    </div>
                </div>
                <flux:button x-data x-on:click="Flux.modal('confirm-sell').show()" variant="primary" icon="check">
                    Potwierdź sprzedaż
                </flux:button>
            </div>

            @error('confirm') <p class="text-sm text-red-500 mt-2">{{ $message }}</p> @enderror
        @else
            <div class="flex flex-col items-center gap-3 text-zinc-400 py-12">
                <flux:icon name="shopping-cart" class="size-10 opacity-30" />
                <span class="text-sm">Wyszukaj przedmioty lub usługi powyżej aby dodać do koszyka</span>
            </div>
        @endif
    </flux:card>

    {{-- Confirmation modal --}}
    <flux:modal name="confirm-sell" class="w-full max-w-lg">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center size-10 rounded-full bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon name="check-circle" variant="mini" class="size-5 text-blue-500" />
                </span>
                <div>
                    <flux:heading size="lg">Potwierdzenie sprzedaży</flux:heading>
                    <flux:text class="text-sm">Sprawdź podsumowanie przed zatwierdzeniem.</flux:text>
                </div>
            </div>

            {{-- Summary table --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-800 text-sm max-h-64 overflow-y-auto">
                @foreach($cart as $entry)
                    <div class="flex items-center justify-between px-3 py-2.5">
                        <div class="flex items-center gap-2 min-w-0">
                            @if($entry['type'] === 'item')
                                <flux:icon.cube variant="micro" class="size-4 text-blue-500 shrink-0" />
                            @else
                                <flux:icon.wrench-screwdriver variant="micro" class="size-4 text-purple-500 shrink-0" />
                            @endif
                            <span class="truncate text-zinc-900 dark:text-zinc-100 font-medium">{{ $entry['name'] }}</span>
                            @if($entry['imei'])
                                <span class="font-mono text-xs text-emerald-600 dark:text-emerald-400 shrink-0">{{ $entry['imei'] }}</span>
                            @endif
                        </div>
                        <span class="tabular-nums font-semibold text-zinc-900 dark:text-zinc-100 shrink-0 ml-3">
                            {{ number_format($entry['price'] / 100, 2, ',', ' ') }} zł
                        </span>
                    </div>
                @endforeach
            </div>

            {{-- Total + payment --}}
            <div class="flex items-center justify-between rounded-lg bg-zinc-50 dark:bg-zinc-800/50 px-4 py-3">
                <div class="flex items-center gap-2">
                    @php $modalPm = \App\Enums\PaymentMethod::from($paymentMethod); @endphp
                    <span class="inline-flex items-center justify-center size-8 rounded-md {{ $modalPm->bgClass() }}">
                        <flux:icon :name="$modalPm->icon()" variant="mini" class="size-4 {{ $modalPm->iconClass() }}" />
                    </span>
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $modalPm->label() }}</span>
                </div>
                <div class="text-right">
                    <span class="text-xs text-zinc-500 dark:text-zinc-400 block">Razem brutto</span>
                    <span class="font-bold tabular-nums text-xl text-zinc-900 dark:text-white">
                        {{ number_format($this->cartTotal / 100, 2, ',', ' ') }} zł
                    </span>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Anuluj</flux:button>
                </flux:modal.close>
                <flux:button wire:click="confirm" variant="primary" icon="check">
                    Zatwierdź sprzedaż
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>