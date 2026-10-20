<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Zakupy</flux:heading>
        <div class="flex items-center gap-3">
            <flux:badge size="lg">{{ $purchases->total() }} szt.</flux:badge>
            @if($this->shop)
                <flux:button variant="primary" icon="plus" size="sm" :href="route('shop.purchases.create', $this->shop)" wire:navigate>
                    Nowy zakup
                </flux:button>
            @endif
        </div>
    </div>

    <div class="mb-4 flex gap-3">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Szukaj dostawcy lub nr faktury..." icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="period" class="w-40">
            <option value="">Cały okres</option>
            @foreach($periods as $p)
                <option value="{{ $p->value }}">{{ $p->label() }}</option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="paymentMethod" class="w-48">
            <option value="">Wszystkie płatności</option>
            @foreach($paymentMethods as $pm)
                <option value="{{ $pm->value }}">{{ $pm->label() }}</option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="status" class="w-44">
            <option value="">Wszystkie</option>
            @foreach(\App\Enums\PurchaseStatus::cases() as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </flux:select>
    </div>

    <flux:table :paginate="$purchases">
        <flux:table.columns>
            <flux:table.column>ID</flux:table.column>
            <flux:table.column>Data</flux:table.column>
            <flux:table.column>Sklep</flux:table.column>
            <flux:table.column>Dostawca</flux:table.column>
            <flux:table.column>Nr faktury</flux:table.column>
            <flux:table.column>Pozycje</flux:table.column>
            <flux:table.column>Netto</flux:table.column>
            <flux:table.column>Brutto</flux:table.column>
            <flux:table.column>Płatność</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Dodał</flux:table.column>

        </flux:table.columns>

        <flux:table.rows>
            @forelse($purchases as $purchase)
                <flux:table.row>
                <flux:table.cell variant="strong" class="tabular-nums">
                    @php
                        $showRoute = $this->shop
                            ? route('shop.purchases.show', [$this->shop, $purchase])
                            : route('purchases.show', $purchase);
                    @endphp
                    <flux:link href="{{ $showRoute }}" wire:navigate class="font-mono">#{{ $purchase->id }}</flux:link>
                </flux:table.cell>                    <flux:table.cell>{{ $purchase->created_at->format('d.m.Y') }}</flux:table.cell>
                    <flux:table.cell>{{ $purchase->shop->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $purchase->contact->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $purchase->invoice_number ?: '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $purchase->getItemsCount() }}</flux:table.cell>
                    <flux:table.cell>{{ number_format($purchase->getTotalNetPrice() / 100, 2, ',', ' ') }} zł</flux:table.cell>
                    <flux:table.cell>{{ number_format($purchase->getTotalGrossPrice() / 100, 2, ',', ' ') }} zł</flux:table.cell>
                    <flux:table.cell>{{ $purchase->payment_method->label() }}</flux:table.cell>
                    <flux:table.cell>
                    @php $status = $purchase->status(); @endphp
                    <flux:badge flux:badge size="sm" :color="$status->color()">{{ $status->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($purchase->creator)
                            <flux:tooltip :content="$purchase->creator->name">
                                <span class="inline-flex">
                                    <flux:avatar size="sm">{{ $purchase->creator->initials() }}</flux:avatar>
                                </span>
                            </flux:tooltip>
                        @else
                            <flux:avatar size="sm">-</flux:avatar>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="10" class="text-center text-zinc-500">
                        Brak zakupów
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>