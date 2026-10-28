<div>
    <div class="mb-4 flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="$this->backUrl" wire:navigate />
        <flux:heading size="xl">Zakup #{{ $purchase->id }}</flux:heading>
        @php $status = $purchase->status(); @endphp
        <flux:badge size="sm" :color="$status->color()">{{ $status->label() }}</flux:badge>

        {{-- Only the author can resume their own draft --}}
        @if($purchase->is_draft && $purchase->created_by === auth()->id())
            <flux:button size="sm" variant="primary" icon="pencil-square" class="ml-auto"
                :href="route('shop.purchases.create', $purchase->shop)" wire:navigate>
                Kontynuuj
            </flux:button>
        @endif
    </div>

    {{-- Header --}}
    <flux:card class="mb-6">
        <div class="grid grid-cols-2 gap-6 lg:grid-cols-4">
            <div>
                <flux:subheading>Sklep</flux:subheading>
                @if($purchase->shop)
                    <div class="mt-1 flex items-center gap-2">
                        <flux:avatar :name="$purchase->shop->name" src="{{ $purchase->shop->avatar_url }}" size="sm" />
                        <flux:heading>{{ $purchase->shop->short_name ?: $purchase->shop->name }}</flux:heading>
                    </div>
                @else
                    <flux:heading class="mt-1">—</flux:heading>
                @endif
            </div>

            <div>
                <flux:subheading>Dostawca</flux:subheading>
                <flux:heading class="mt-1">{{ $purchase->contact?->name ?? '—' }}</flux:heading>
                @if($purchase->contact?->identity_number)
                    <flux:text size="sm">{{ $purchase->contact->identity_number }}</flux:text>
                @endif
            </div>

            <div>
                <flux:subheading>Płatność</flux:subheading>
                <flux:heading class="mt-1">{{ $purchase->payment_method?->label() ?? '—' }}</flux:heading>
            </div>

            <div>
                <flux:subheading>Nr faktury</flux:subheading>
                <flux:heading class="mt-1">{{ $purchase->invoice_number ?: '—' }}</flux:heading>
            </div>

            <div>
                <flux:subheading>Data</flux:subheading>
                <flux:heading class="mt-1">{{ $purchase->created_at?->format('d.m.Y H:i') ?? '—' }}</flux:heading>
            </div>

            <div>
                <flux:subheading>Utworzył</flux:subheading>
                @if($purchase->creator)
                    <div class="mt-1 flex items-center gap-2">
                        <flux:avatar size="sm">{{ $purchase->creator->initials() }}</flux:avatar>
                        <flux:heading>{{ $purchase->creator->name }}</flux:heading>
                    </div>
                @else
                    <flux:heading class="mt-1">—</flux:heading>
                @endif
            </div>
        </div>
    </flux:card>

    {{-- Lines --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Produkt</flux:table.column>
            <flux:table.column>Stan</flux:table.column>
            <flux:table.column>Kolor</flux:table.column>
            <flux:table.column>IMEI</flux:table.column>
            <flux:table.column class="text-center">Szt.</flux:table.column>
            <flux:table.column>Netto / szt.</flux:table.column>
            <flux:table.column>VAT</flux:table.column>
            <flux:table.column>Brutto razem</flux:table.column>
            <flux:table.column>Status</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->lines as $line)
                @php
                    $pi = $line['pi'];
                    $item = $pi->item;
                @endphp
                <flux:table.row wire:key="line-{{ $pi->id }}">
                    <flux:table.cell variant="strong">
                        {{ trim(($item?->product?->brand?->name ?? '') . ' ' . ($item?->product?->name ?? '—')) }}
                    </flux:table.cell>                    <flux:table.cell class="text-sm text-zinc-500">{{ $item?->condition?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $item?->color?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-sm text-emerald-600">{{ $item?->feature_imei ?: '—' }}</flux:table.cell>
                    <flux:table.cell class="text-center tabular-nums">{{ $line['qty'] }}</flux:table.cell>
                    <flux:table.cell class="tabular-nums whitespace-nowrap">{{ number_format($pi->price / 100, 2, ',', ' ') }} zł</flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $pi->tax?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="tabular-nums whitespace-nowrap font-medium">{{ number_format($pi->getGrossPrice() * $line['qty'] / 100, 2, ',', ' ') }} zł</flux:table.cell>
                    <flux:table.cell>
                        @if($item?->status)
                            <flux:badge size="sm" :color="$item->status->color()">{{ $item->status->label() }}</flux:badge>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="9" class="text-center text-zinc-500">Brak pozycji</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Summary --}}
    @php
        $net = $purchase->getTotalNetPrice();
        $gross = $purchase->getTotalGrossPrice();
    @endphp
    <flux:card class="mt-6">
        <div class="grid grid-cols-2 gap-6 lg:grid-cols-4">
            <div>
                <flux:subheading>Sztuk</flux:subheading>
                <flux:heading size="lg" class="mt-1">{{ $purchase->getItemsCount() }}</flux:heading>
            </div>
            <div>
                <flux:subheading>Netto</flux:subheading>
                <flux:heading size="lg" class="mt-1 tabular-nums">{{ number_format($net / 100, 2, ',', ' ') }} zł</flux:heading>
            </div>
            <div>
                <flux:subheading>VAT</flux:subheading>
                <flux:heading size="lg" class="mt-1 tabular-nums">{{ number_format(($gross - $net) / 100, 2, ',', ' ') }} zł</flux:heading>
            </div>
            <div>
                <flux:subheading>Brutto</flux:subheading>
                <flux:heading size="lg" class="mt-1 tabular-nums">{{ number_format($gross / 100, 2, ',', ' ') }} zł</flux:heading>
            </div>
        </div>
    </flux:card>
</div>