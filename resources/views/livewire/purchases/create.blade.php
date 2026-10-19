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

    {{-- Step 3: Add items --}}
    @else
        {{-- Compact header summary --}}
        <div class="mb-4 flex flex-wrap items-center gap-x-4 gap-y-1 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 px-4 py-2.5 text-sm">
            <span class="text-zinc-500">Dostawca:</span>
            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $contactName }}</span>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Left column: item form --}}
            <div>
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 space-y-4">
                    <flux:heading size="sm">Dodaj przedmiot</flux:heading>

                    {{-- Product search --}}
                    <div>
                        @if($productId)
                            <div class="flex items-center gap-2">
                                <flux:label>Produkt</flux:label>
                                <flux:badge color="zinc">{{ $productName }}</flux:badge>
                                <flux:button wire:click="clearProduct" variant="ghost" size="sm" icon="x-mark" />
                            </div>
                        @else
                            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                <flux:input
                                    wire:model.live.debounce.300ms="productSearch"
                                    label="Produkt"
                                    placeholder="Szukaj produktu..."
                                    @focus="open = true"
                                    @input="open = true"
                                />
                                <div
                                    x-show="open"
                                    x-cloak
                                    class="absolute z-50 mt-1 w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-lg max-h-60 overflow-y-auto"
                                >
                                    @if($this->productResults->isNotEmpty())
                                        @foreach($this->productResults as $product)
                                            <button
                                                type="button"
                                                wire:click="selectProduct({{ $product->id }})"
                                                @click="open = false"
                                                class="w-full px-3 py-2 text-left text-sm text-zinc-900 dark:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                                            >
                                                {{ $product->display_name }}
                                            </button>
                                        @endforeach
                                    @elseif(strlen($productSearch) >= 2)
                                        <div class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">Nie znaleziono</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <flux:select wire:model="conditionId" label="Stan">
                        <option value="">Wybierz...</option>
                        @foreach($this->conditions as $cond)
                            <option value="{{ $cond->id }}">{{ $cond->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="priceNet" label="Cena netto (zł)" placeholder="0.00" type="number" step="0.01" min="0" />

                    <flux:select wire:model="taxId" label="VAT">
                        <option value="">Wybierz...</option>
                        @foreach($this->taxes as $tax)
                            <option value="{{ $tax->id }}">{{ $tax->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="colorId" label="Kolor">
                        <option value="">Bez koloru</option>
                        @foreach($this->colors as $color)
                            <option value="{{ $color->id }}">{{ $color->name }}</option>
                        @endforeach
                    </flux:select>

                    @if($isDevice)
                        <flux:input wire:model="featureImei" label="IMEI" placeholder="Opcjonalnie..." />
                        <flux:input wire:model="featureStorage" label="Storage (GB)" type="number" step="1" min="0" />
                        <div class="flex flex-wrap gap-4">
                            <flux:checkbox wire:model="featureBox" label="Pudełko" />
                            <flux:checkbox wire:model="featureProofOfPurchase" label="Dowód zakupu" />
                        </div>
                    @endif

                    <flux:input wire:model="featureComment" label="Komentarz" placeholder="Opcjonalnie..." />

                    @error('productId') <p class="text-sm text-red-500">{{ $message }}</p> @enderror
                    @error('conditionId') <p class="text-sm text-red-500">{{ $message }}</p> @enderror
                    @error('priceNet') <p class="text-sm text-red-500">{{ $message }}</p> @enderror
                    @error('taxId') <p class="text-sm text-red-500">{{ $message }}</p> @enderror

                    <flux:button wire:click="addItem" variant="primary" icon="plus" class="w-full">
                        Dodaj przedmiot
                    </flux:button>
                </div>
            </div>

            {{-- Right column: items list + summary (2/3) --}}
            <div class="lg:col-span-2 space-y-6">
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
                    @else
                        <div class="flex flex-col items-center gap-3 text-zinc-400 py-12">
                            <flux:icon name="inbox" class="size-10 opacity-30" />
                            <span class="text-sm">Dodaj przedmioty z formularza po lewej</span>
                        </div>
                    @endif
                </div>

                {{-- Summary --}}
                @if($this->draftItems->isNotEmpty())
                    @php
                        $totalNet = $this->draftItems->sum('price');
                        $totalGross = $this->draftItems->sum(fn ($pi) => $pi->getGrossPrice());
                    @endphp
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                        <div class="flex items-center justify-between mb-4">
                            <flux:heading size="sm">Podsumowanie</flux:heading>
                        </div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-zinc-500">Netto:</span>
                            <span class="font-medium tabular-nums">{{ number_format($totalNet / 100, 2, ',', ' ') }} zł</span>
                        </div>
                        <div class="flex justify-between text-sm mb-4">
                            <span class="text-zinc-500">Brutto:</span>
                            <span class="font-semibold tabular-nums">{{ number_format($totalGross / 100, 2, ',', ' ') }} zł</span>
                        </div>

                        @error('confirm') <p class="text-sm text-red-500 mb-2">{{ $message }}</p> @enderror

                        <div class="flex gap-3">
                            <flux:button wire:click="confirm" variant="primary" wire:confirm="Czy na pewno chcesz potwierdzić zakup?" class="flex-1">
                                Potwierdź zakup
                            </flux:button>
                            <flux:button wire:click="cancelDraft" variant="ghost" class="text-red-500" wire:confirm="Czy na pewno chcesz usunąć tę wersję roboczą?">
                                Usuń
                            </flux:button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>