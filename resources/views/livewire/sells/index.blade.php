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

    

    <div class="mb-4 flex flex-wrap gap-3">
        <div class="flex-1 min-w-48">
            <flux:input wire:model.live.debounce.500ms="search" placeholder="ID sprzedaży lub nazwa produktu..." icon="magnifying-glass" />
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
        <flux:select wire:model.live="valid" class="w-36">
            <option value="all">Wszystkie</option>
            <option value="1">Aktywne</option>
            <option value="0">Anulowane</option>
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
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($sells as $sell)
                <flux:table.row>
                    <flux:table.cell variant="strong">{{ $sell->id }}</flux:table.cell>
                    <flux:table.cell>
                        @foreach($sell->soldItems as $si)
                            <div class="flex justify-between gap-4 text-sm leading-tight {{ !$loop->first ? 'mt-0.5' : '' }}">
                                <span>
                                    @if($si->item?->product)
                                        {{ $si->item->product->brand?->name }} {{ $si->item->product->name }}
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
                        {{ \Carbon\Carbon::createFromTimestamp($sell->created_at)->format('d.m.Y H:i') }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($sell->valid)
                            <flux:icon.check-circle variant="mini" class="size-5 text-green-500" />
                        @else
                            <flux:icon.x-circle variant="mini" class="size-5 text-red-500" />
                        @endif
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