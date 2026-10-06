<div>
    <flux:heading size="xl" class="mb-1">Nowy transfer</flux:heading>
    <flux:subheading class="mb-4">Z: {{ $shop->name }}</flux:subheading>

    <form wire:submit="addItem" class="mb-4 max-w-md">
        <flux:input
            wire:model="barcode"
            label="Kod kreskowy lub ID itema"
            placeholder="Zeskanuj kod lub wpisz ID i wciśnij Enter"
            autofocus
        />
    </form>

    <flux:table class="mb-6">
        <flux:table.columns>
            <flux:table.column>ID</flux:table.column>
            <flux:table.column>Produkt</flux:table.column>
            <flux:table.column>Stan</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->cartLines as $line)
                <flux:table.row wire:key="cart-line-{{ $line->id }}">
                    <flux:table.cell variant="strong">{{ $line->item_id }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $line->item?->product?->brand?->name }} {{ $line->item?->product?->name }}
                    </flux:table.cell>
                    <flux:table.cell>{{ $line->item?->condition?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if(isset($this->unavailableReasons[$line->item_id]))
                            <flux:badge size="sm" color="red">
                                Niedostępny: {{ $this->unavailableReasons[$line->item_id] }}
                            </flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeItem({{ $line->id }})" />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center text-zinc-500">
                        Koszyk jest pusty
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <form wire:submit="send" class="max-w-md space-y-4">
        <flux:select wire:model="targetShopId" label="Sklep docelowy">
            <option value="">Wybierz sklep...</option>
            @foreach($this->targetShops as $targetShop)
                <option value="{{ $targetShop->id }}">{{ $targetShop->name }}</option>
            @endforeach
        </flux:select>

        <flux:error name="send" />

        <flux:button
            type="submit"
            variant="primary"
            :disabled="$this->cartLines->isEmpty() || count($this->unavailableReasons) > 0"
        >
            Wyślij transfer ({{ $this->cartLines->count() }})
        </flux:button>
    </form>
</div>