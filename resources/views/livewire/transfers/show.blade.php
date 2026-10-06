<div>
    <div class="mb-4 flex items-center gap-3">
        <flux:heading size="xl">Transfer #{{ $transfer->id }}</flux:heading>
        <flux:badge :color="$transfer->status->color()">{{ $transfer->status->label() }}</flux:badge>
    </div>

    <flux:card class="mb-6">
        <div class="grid grid-cols-2 gap-6 lg:grid-cols-4">
            <div>
                <flux:subheading>Z</flux:subheading>
                <flux:heading class="mt-1">
                    {{ $transfer->sourceShop?->short_name ?: $transfer->sourceShop?->name ?? '—' }}
                </flux:heading>
            </div>

            <div>
                <flux:subheading>Do</flux:subheading>
                <flux:heading class="mt-1">
                    {{ $transfer->targetShop?->short_name ?: $transfer->targetShop?->name ?? '—' }}
                </flux:heading>
            </div>

            @foreach([
                ['label' => 'Utworzył', 'user' => $transfer->creator,  'date' => $transfer->created_at],
                ['label' => 'Zamknął',  'user' => $transfer->finisher, 'date' => $transfer->finished_at],
            ] as $person)
                <div>
                    <flux:subheading>{{ $person['label'] }}</flux:subheading>

                    @if($person['user'])
                        <div class="mt-1 flex items-center gap-2">
                            <flux:avatar size="sm">{{ $person['user']->initials() }}</flux:avatar>
                            <flux:heading>{{ $person['user']->name }}</flux:heading>
                        </div>
                    @else
                        <flux:heading class="mt-1">—</flux:heading>
                    @endif

                    <flux:text size="sm" class="mt-1">
                        {{ $person['date']?->format('d.m.Y H:i') ?? '—' }}
                    </flux:text>
                </div>
            @endforeach
        </div>
    </flux:card>

    <flux:error name="action" class="mb-4" />

    @if($canReceive || $canCancelOrLose)
        <div class="mb-6 flex gap-2">
            @if($canReceive)
                <flux:modal.trigger name="receive">
                    <flux:button variant="primary" icon="check">Przyjmij</flux:button>
                </flux:modal.trigger>
            @endif

            @if($canCancelOrLose)
                <flux:modal.trigger name="cancel">
                    <flux:button icon="x-mark">Anuluj</flux:button>
                </flux:modal.trigger>

                <flux:modal.trigger name="lost">
                    <flux:button variant="danger" icon="exclamation-triangle">Zgubiony</flux:button>
                </flux:modal.trigger>
            @endif
        </div>

        @if($canReceive)
            <flux:modal name="receive" class="min-w-[22rem]">
                <flux:heading size="lg">Przyjąć transfer?</flux:heading>
                <flux:text class="mt-2">
                    {{ $transfer->transferItems->count() }} szt. trafi na magazyn sklepu {{ $transfer->targetShop?->name }}.
                </flux:text>
                <div class="mt-6 flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">Wróć</flux:button></flux:modal.close>
                    <flux:button variant="primary" wire:click="receive">Przyjmij</flux:button>
                </div>
            </flux:modal>
        @endif

        @if($canCancelOrLose)
            <flux:modal name="cancel" class="min-w-[22rem]">
                <flux:heading size="lg">Anulować transfer?</flux:heading>
                <flux:text class="mt-2">
                    Itemy wrócą na magazyn sklepu {{ $transfer->sourceShop?->name }}.
                </flux:text>
                <div class="mt-6 flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">Wróć</flux:button></flux:modal.close>
                    <flux:button variant="primary" wire:click="cancel">Anuluj transfer</flux:button>
                </div>
            </flux:modal>

            <flux:modal name="lost" class="min-w-[22rem]">
                <flux:heading size="lg">Oznaczyć jako zgubiony?</flux:heading>
                <flux:text class="mt-2">
                    Wszystkie itemy z tego transferu dostaną status „Zagubiony”.
                </flux:text>
                <div class="mt-6 flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">Wróć</flux:button></flux:modal.close>
                    <flux:button variant="danger" wire:click="markLost">Oznacz jako zgubiony</flux:button>
                </div>
            </flux:modal>
        @endif
    @endif

    <flux:table>
        <flux:table.columns>
            <flux:table.column>ID</flux:table.column>
            <flux:table.column>Produkt</flux:table.column>
            <flux:table.column>Stan</flux:table.column>
            <flux:table.column>Status itema</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($transfer->transferItems as $line)
                <flux:table.row wire:key="transfer-line-{{ $line->id }}">
                    <flux:table.cell variant="strong">{{ $line->item_id }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $line->item?->product?->brand?->name }} {{ $line->item?->product?->name }}
                    </flux:table.cell>
                    <flux:table.cell>{{ $line->item?->condition?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if($line->item)
                            <flux:badge size="sm" :color="$line->item->status->color()">
                                {{ $line->item->status->label() }}
                            </flux:badge>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" class="text-center text-zinc-500">
                        Brak pozycji
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>