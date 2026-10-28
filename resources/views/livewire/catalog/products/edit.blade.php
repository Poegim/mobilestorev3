<div class="max-w-2xl">
    <div class="mb-6 flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('catalog.products.index')" wire:navigate />
        <flux:heading size="xl">Edytuj produkt #{{ $product->id }}</flux:heading>
    </div>

    <div class="space-y-6">
        {{-- Brand --}}
        <div>
            @if($showNewBrand)
                <div class="flex gap-2 items-end">
                    <div class="flex-1">
                        <flux:input wire:model="newBrandName" label="Nowa marka" placeholder="Nazwa marki..." autofocus />
                    </div>
                    <flux:button wire:click="createBrand" variant="primary" size="sm">Dodaj</flux:button>
                    <flux:button wire:click="$set('showNewBrand', false)" variant="ghost" size="sm">Anuluj</flux:button>
                </div>
            @else
                <div class="flex gap-2 items-end">
                    <div class="flex-1">
                        <flux:select wire:model.live="brandId" label="Marka" placeholder="Bez marki">
                            <option value="">Bez marki</option>
                            @foreach($this->brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <flux:button wire:click="$set('showNewBrand', true)" variant="ghost" size="sm" icon="plus">Nowa</flux:button>
                </div>
            @endif
        </div>

        {{-- Name --}}
        <flux:input wire:model="name" label="Nazwa produktu" placeholder="np. Flexible Glass" />

        {{-- Category --}}
        <flux:select wire:model="categoryId" label="Kategoria">
            <option value="">Wybierz kategorię...</option>
            @foreach($this->categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </flux:select>


        {{-- Submit --}}
        <div class="flex gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
            <flux:button wire:click="save" variant="primary">Zapisz zmiany</flux:button>
            <flux:button :href="route('catalog.products.index')" wire:navigate variant="ghost">Anuluj</flux:button>
        </div>
    </div>
</div>