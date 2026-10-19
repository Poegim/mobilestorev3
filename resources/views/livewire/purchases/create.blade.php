<div>
    <div class="mb-6 flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('shop.purchases.index', $shop)" wire:navigate />
        <flux:heading size="xl">Nowy zakup — {{ $shop->name }}</flux:heading>
        @if($purchaseType)
            <flux:badge color="zinc" size="sm">{{ \App\Enums\PurchaseType::from($purchaseType)->label() }}</flux:badge>
        @endif
        @if($purchaseId)
            <flux:badge color="amber" size="sm">Wersja robocza #{{ $purchaseId }}</flux:badge>
        @endif
    </div>

    {{-- Step 1: Choose purchase type --}}
    @if($step === 1)
        <div class="max-w-md mx-auto mt-12 space-y-6 text-center">
            <flux:heading size="lg">Wybierz typ zakupu</flux:heading>
            <div class="flex flex-col gap-3">
                @foreach(\App\Enums\PurchaseType::cases() as $type)
                    <flux:button
                        wire:click="$set('purchaseType', {{ $type->value }})"
                        variant="ghost"
                        class="justify-center py-6 text-base border border-zinc-200 dark:border-zinc-700 hover:border-blue-500"
                    >
                        {{ $type->label() }}
                    </flux:button>
                @endforeach
            </div>
        </div>

    {{-- Step 2: Purchase header --}}
    @elseif($step === 2)
        <div class="max-w-lg mx-auto mt-8 space-y-6">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 space-y-4">
                <flux:heading size="lg">Dane zakupu</flux:heading>

                {{-- Contact search --}}
                <div>
                    @if($contactId)
                        <div class="flex items-center gap-2">
                            <flux:label>Dostawca</flux:label>
                            <flux:badge color="zinc">{{ $contactName }}</flux:badge>
                            <flux:button wire:click="clearContact" variant="ghost" size="sm" icon="x-mark" />
                        </div>
                    @else
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <flux:input
                                wire:model.live.debounce.300ms="contactSearch"
                                label="Dostawca"
                                placeholder="Szukaj po nazwie lub NIP..."
                                @focus="open = true"
                                @input="open = true"
                            />
                            <div
                                x-show="open"
                                x-cloak
                                class="absolute z-50 mt-1 w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-lg max-h-48 overflow-y-auto"
                            >
                                @if($this->contactResults->isNotEmpty())
                                    @foreach($this->contactResults as $contact)
                                        <button
                                            type="button"
                                            wire:click="selectContact({{ $contact->id }})"
                                            @click="open = false"
                                            class="w-full px-3 py-2 text-left text-sm text-zinc-900 dark:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                                        >
                                            <span class="font-medium">{{ $contact->name }}</span>
                                            @if($contact->identity_number)
                                                <span class="text-zinc-400 ml-2">{{ $contact->identity_number }}</span>
                                            @endif
                                        </button>
                                    @endforeach
                                @elseif(strlen($contactSearch) >= 2)
                                    <div class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">Nie znaleziono</div>
                                @endif
                            </div>
                        </div>
                    @endif
                    @error('contactId') <p class="text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <flux:select wire:model="paymentMethod" label="Metoda płatności">
                    @foreach($this->paymentMethods as $pm)
                        <option value="{{ $pm->value }}">{{ $pm->label() }}</option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="invoiceNumber" label="Nr faktury" placeholder="Opcjonalnie..." />

                <flux:button wire:click="nextStep" variant="primary" class="w-full mt-2">
                    Dalej
                </flux:button>
            </div>
        </div>

    @else
        {{-- Compact header summary --}}
        <div class="mb-4 flex flex-wrap items-center gap-x-4 gap-y-1 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 px-4 py-2.5 text-sm">
            <span class="text-zinc-500">Dostawca:</span>
            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $contactName ?? '—' }}</span>
            <span class="text-zinc-300 dark:text-zinc-600 select-none">|</span>
            <span class="text-zinc-500">Płatność:</span>
            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ \App\Enums\PaymentMethod::from($paymentMethod)->label() }}</span>
            @if($invoiceNumber)
                <span class="text-zinc-300 dark:text-zinc-600 select-none">|</span>
                <span class="text-zinc-500">FV:</span>
                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $invoiceNumber }}</span>
            @endif
            <button type="button" wire:click="backToHeader" class="ml-auto text-blue-500 hover:underline text-xs">Zmień</button>
        </div>

        {{-- Add item section --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 mb-4">

            {{-- Selected product + item properties --}}
            @if($productId)
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <flux:heading size="sm">Wybrany produkt:</flux:heading>
                            <flux:badge color="blue">{{ $productName }}</flux:badge>
                        </div>
                        <flux:button wire:click="clearProduct" variant="ghost" size="sm" icon="x-mark">Zmień produkt</flux:button>
                    </div>

                    <div class="flex flex-wrap gap-2 items-end">
                        <flux:select wire:model="conditionId" label="Stan" class="w-32">
                            @foreach($this->conditions as $cond)
                                <option value="{{ $cond->id }}">{{ $cond->name }}</option>
                            @endforeach
                        </flux:select>

                        <flux:input wire:model="priceNet" label="Cena netto (zł)" placeholder="0.00" type="number" step="0.01" min="0" class="w-32" />

                        <flux:select wire:model="taxId" label="VAT" class="w-24">
                            @foreach($this->taxes as $tax)
                                <option value="{{ $tax->id }}">{{ $tax->name }}</option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="colorId" label="Kolor" class="w-36">
                            <option value="">Bez koloru</option>
                            @foreach($this->colors as $color)
                                <option value="{{ $color->id }}">{{ $color->name }}</option>
                            @endforeach
                        </flux:select>

                        @if($isDevice)
                            <flux:input wire:model="featureImei" label="IMEI" placeholder="Opcjonalnie..." class="w-44" />
                            <flux:input wire:model="featureStorage" label="Storage (GB)" type="number" step="1" min="0" class="w-28" />
                            <div class="flex items-center gap-3 pb-1">
                                <flux:checkbox wire:model="featureBox" label="Pudełko" />
                                <flux:checkbox wire:model="featureProofOfPurchase" label="Dowód" />
                            </div>
                        @endif

                        <flux:input wire:model="featureComment" label="Komentarz" placeholder="Opcjonalnie..." class="w-44" />

                        <flux:button wire:click="addItem" variant="primary" icon="plus" class="mb-px">
                            Dodaj
                        </flux:button>
                    </div>

                    @if($errors->any())
                        <div class="flex flex-wrap gap-3">
                            @error('productId') <p class="text-sm text-red-500">{{ $message }}</p> @enderror
                            @error('conditionId') <p class="text-sm text-red-500">{{ $message }}</p> @enderror
                            @error('priceNet') <p class="text-sm text-red-500">{{ $message }}</p> @enderror
                            @error('taxId') <p class="text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>

            {{-- Product search --}}
            @else
                <flux:heading size="sm" class="mb-3">Znajdź produkt</flux:heading>

                {{-- Filters --}}
                <div class="flex flex-wrap gap-2 items-end mb-3">
                    <div class="flex-1 min-w-56">
                        <flux:input
                            wire:model.live.debounce.300ms="productSearch"
                            placeholder="Szukaj po nazwie..."
                            icon="magnifying-glass"
                            clearable
                        />
                    </div>

                    <flux:select wire:model.live="filterBrand" class="w-40">
                        <option value="">Marka</option>
                        @foreach($this->brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="filterCategory" class="w-44">
                        <option value="">Kategoria</option>
                        @foreach($this->categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </flux:select>

                    <div class="w-52 relative" x-data="{ open: false }" @click.outside="open = false">
                        @if($filterDeviceId)
                            <div class="flex items-center gap-1.5 h-[38px]">
                                <flux:badge color="cyan" size="sm">{{ $filterDeviceName }}</flux:badge>
                                <flux:button wire:click="clearDeviceFilter" variant="ghost" size="sm" icon="x-mark" />
                            </div>
                        @else
                            <flux:input
                                wire:model.live.debounce.300ms="filterDevice"
                                placeholder="Urządzenie..."
                                clearable
                                @focus="open = true"
                                @input="open = true"
                            />
                            <div
                                x-show="open"
                                x-cloak
                                class="absolute z-50 mt-1 w-72 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-lg max-h-48 overflow-y-auto"
                            >
                                @if($this->deviceFilterResults->isNotEmpty())
                                    @foreach($this->deviceFilterResults as $device)
                                        <button
                                            type="button"
                                            wire:click="selectDeviceFilter({{ $device->id }})"
                                            @click="open = false"
                                            class="w-full px-3 py-2 text-left text-sm text-zinc-900 dark:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                                        >
                                            {{ $device->full_name }}
                                        </button>
                                    @endforeach
                                @elseif(strlen($filterDevice) >= 2)
                                    <div class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">Nie znaleziono</div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Results --}}
                @if($this->productResults->isNotEmpty())
                    <div class="max-h-72 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($this->productResults as $product)
                            <button
                                type="button"
                                wire:click="selectProduct({{ $product->id }})"
                                class="w-full px-3 py-1.5 text-left text-sm flex items-center gap-3 hover:bg-blue-50 dark:hover:bg-blue-950/30 transition-colors"
                            >
                                <span class="font-medium text-zinc-900 dark:text-zinc-100 truncate">{{ $product->brand?->name }}</span>
                                <span class="text-zinc-600 dark:text-zinc-300 truncate">{{ $product->name }}</span>
                                @if($product->devices->isNotEmpty())
                                    <span class="text-zinc-400 text-xs truncate shrink-0">
                                        {{ $product->devices->pluck('full_name')->join(', ') }}
                                    </span>
                                @endif
                                <span class="text-zinc-300 dark:text-zinc-600 text-xs ml-auto shrink-0">{{ $product->category?->name }}</span>
                            </button>
                        @endforeach
                    </div>
                @elseif(strlen($productSearch) >= 2 || $filterBrand || $filterCategory || $filterDeviceId)
                    <div class="text-sm text-zinc-400 text-center py-6">
                        Nie znaleziono produktów
                    </div>
                @else
                    <div class="text-sm text-zinc-400 text-center py-6">
                        Wpisz nazwę lub użyj filtrów aby znaleźć produkt
                    </div>
                @endif
            @endif
        </div>

        {{-- Draft items table --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="sm">Przedmioty</flux:heading>
                @if($this->draftItems->isNotEmpty())
                    <flux:badge size="sm">{{ $this->draftItems->count() }} szt.</flux:badge>
                @endif
            </div>

            @if($this->draftItems->isNotEmpty())
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Produkt</flux:table.column>
                        <flux:table.column>Stan</flux:table.column>
                        <flux:table.column>Kolor</flux:table.column>
                        <flux:table.column>IMEI</flux:table.column>
                        <flux:table.column>Netto</flux:table.column>
                        <flux:table.column>VAT</flux:table.column>
                        <flux:table.column>Brutto</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($this->draftItems as $pi)
                            <flux:table.row>
                                <flux:table.cell variant="strong">
                                    {{ $pi->item?->product?->display_name ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell class="text-sm text-zinc-500">
                                    {{ $pi->item?->condition?->name ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell class="text-sm text-zinc-500">
                                    {{ $pi->item?->color?->name ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell class="font-mono text-sm text-emerald-600">
                                    {{ $pi->item?->feature_imei ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell class="tabular-nums">
                                    {{ number_format($pi->price / 100, 2, ',', ' ') }} zł
                                </flux:table.cell>
                                <flux:table.cell class="text-sm text-zinc-500">
                                    {{ $pi->tax?->name ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell class="tabular-nums font-medium">
                                    {{ number_format($pi->getGrossPrice() / 100, 2, ',', ' ') }} zł
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:button wire:click="removeItem({{ $pi->id }})" variant="ghost" size="sm" icon="trash" class="text-red-500" />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                @php
                    $totalNet = $this->draftItems->sum('price');
                    $totalGross = $this->draftItems->sum(fn ($pi) => $pi->getGrossPrice());
                @endphp
                <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                    <div class="flex gap-6 text-sm">
                        <span>
                            <span class="text-zinc-500">Netto:</span>
                            <span class="font-medium tabular-nums ml-1">{{ number_format($totalNet / 100, 2, ',', ' ') }} zł</span>
                        </span>
                        <span>
                            <span class="text-zinc-500">Brutto:</span>
                            <span class="font-semibold tabular-nums ml-1">{{ number_format($totalGross / 100, 2, ',', ' ') }} zł</span>
                        </span>
                    </div>
                    <div class="flex gap-3">
                        <flux:button wire:click="cancelDraft" variant="ghost" class="text-red-500" wire:confirm="Czy na pewno chcesz usunąć tę wersję roboczą?">
                            Usuń
                        </flux:button>
                        <flux:button wire:click="confirm" variant="primary" wire:confirm="Czy na pewno chcesz potwierdzić zakup?">
                            Potwierdź zakup
                        </flux:button>
                    </div>
                </div>

                @error('confirm') <p class="text-sm text-red-500 mt-2">{{ $message }}</p> @enderror
            @else
                <div class="flex flex-col items-center gap-3 text-zinc-400 py-12">
                    <flux:icon name="inbox" class="size-10 opacity-30" />
                    <span class="text-sm">Dodaj przedmioty korzystając z formularza powyżej</span>
                </div>
            @endif
        </div>
    @endif
</div>