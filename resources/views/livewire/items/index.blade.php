<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Magazyn</flux:heading>
        <flux:badge size="lg">{{ $items->total() }} szt.</flux:badge>
    </div>

    <div class="mb-4 flex gap-3">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Szukaj produktu..." icon="magnifying-glass" />
        </div>
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
                        <span class="font-medium">{{ $item->product->brand->name ?? '' }} {{ $item->product->name }}</span>
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500">{{ $item->product->category->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $item->condition->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell>
                        @if($item->feature_price)
                            {{ number_format($item->feature_price / 100, 2, ',', ' ') }} zł
                        @else
                            -
                        @endif
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