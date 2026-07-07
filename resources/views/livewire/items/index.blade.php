<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Magazyn</flux:heading>
        <flux:badge size="lg">{{ $items->total() }} szt.</flux:badge>
    </div>

    <div class="mb-4 flex gap-3">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.500ms="search" placeholder="ID, produkt lub IMEI..." icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="brand" class="w-48">
            <option value="">Wszystkie marki</option>
            @foreach($brands as $b)
                <option value="{{ $b->id }}">{{ $b->name }}</option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="period" class="w-48">
            @foreach(\App\Enums\Period::cases() as $p)
                <option value="{{ $p->value }}">{{ $p->label() }}</option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="category" class="w-56">
            <option value="">Wszystkie kategorie</option>
            @foreach($this->categoryTree as $group)
                <optgroup label="{{ $group['name'] }}">
                    <option value="{{ $group['id'] }}">Wszystkie {{ $group['name'] }}</option>
                    @foreach($group['children'] as $child)
                        <option value="{{ $child['id'] }}">
                            {{ str_repeat('── ', $child['depth']) }}{{ $child['name'] }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="status" class="w-48">
            <option value="all">Wszystkie statusy</option>
            @foreach($statuses as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </flux:select>
    </div>

    <flux:table :paginate="$items">
        <flux:table.columns>
            <flux:table.column>ID</flux:table.column>
            <flux:table.column>Produkt</flux:table.column>
            <flux:table.column>Kategoria</flux:table.column>
            <flux:table.column>IMEI</flux:table.column>
            <flux:table.column>Stan</flux:table.column>
            <flux:table.column>Cena</flux:table.column>
            <flux:table.column>Zakup</flux:table.column>
            <flux:table.column>DSNP</flux:table.column>
            <flux:table.column>Status</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($items as $item)
                <flux:table.row>
                    <flux:table.cell>{{ $item->id }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="w-64 whitespace-normal break-words font-medium">
                            {{ $item->product->brand->name ?? '' }} {{ $item->product->name }}
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500">{{ $item->product->category->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-sm">{{ $item->feature_imei ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $item->condition->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap tabular-nums text-sm">
                        @php
                            $pi = $item->purchasedItem;
                            $sell = $item->getSellingPrice(); // suggested gross sell price (grosze)
                        @endphp
                        <div class="flex flex-col gap-0.5">
                            {{-- Purchase: net / gross --}}
                            @if($pi)
                                <span class="text-zinc-500">
                                    zakup: {{ number_format($pi->price / 100, 2, ',', ' ') }}
                                    / {{ number_format($pi->getGrossPrice() / 100, 2, ',', ' ') }} zł
                                </span>
                            @else
                                <span class="text-zinc-300 dark:text-zinc-600">zakup: —</span>
                            @endif

                            {{-- Suggested sell price (gross) --}}
                            <span class="font-medium">
                                sprzedaż: {{ $sell !== null ? number_format($sell / 100, 2, ',', ' ') . ' zł' : '—' }}
                            </span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($item->purchasedItem?->purchase)
                            <div class="text-sm">
                                <div>FV: {{ $item->purchasedItem->purchase->invoice_number ?: 'brak' }}</div>
                                <div class="text-zinc-500">{{ Str::limit($item->purchasedItem->purchase->contact->name ?? '-', 20) }}</div>
                                <div class="text-zinc-400 text-xs">
                                    {{ $item->purchasedItem->purchase->created_at->format('d.m.Y') }}
                                </div>
                            </div>
                        @else
                            -
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($item->displaced_at)
                            <div class="text-sm">
                                <span class="font-medium">{{ $item->days_on_shelf }} dni</span>
                                <div class="text-zinc-400 text-xs">od {{ $item->displaced_at->format('d.m.Y') }}</div>
                            </div>
                        @else
                            -
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :variant="$item->status->color() === 'green' ? 'primary' : 'outline'">
                            {{ $item->status->label() }}
                        </flux:badge>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center text-zinc-500">
                        Brak itemów
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>