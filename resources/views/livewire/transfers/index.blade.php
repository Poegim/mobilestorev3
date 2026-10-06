<div>
    <flux:heading size="xl" class="mb-4">Transfery</flux:heading>

    <div class="mb-4 flex gap-3">
        @if($this->shop)
            <flux:select wire:model.live="direction" class="w-48">
                <option value="all">Wszystkie</option>
                <option value="out">Wychodzące</option>
                <option value="in">Przychodzące</option>
            </flux:select>
        @endif

        <flux:select wire:model.live="status" class="w-48">
            <option value="">Wszystkie statusy</option>
            @foreach($this->statuses as $transferStatus)
                <option value="{{ $transferStatus->value }}">{{ $transferStatus->label() }}</option>
            @endforeach
        </flux:select>
    </div>

    <flux:table :paginate="$transfers">
        <flux:table.columns>
            <flux:table.column>ID</flux:table.column>
            <flux:table.column>Z</flux:table.column>
            <flux:table.column>Do</flux:table.column>
            <flux:table.column>Szt.</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Utworzył</flux:table.column>
            <flux:table.column>Utworzono</flux:table.column>
            <flux:table.column>Zakończono</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($transfers as $transfer)
                <flux:table.row>
                    <flux:table.cell variant="strong">#{{ $transfer->id }}</flux:table.cell>
                    <flux:table.cell>{{ $transfer->sourceShop?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $transfer->targetShop?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $transfer->transfer_items_count }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$transfer->status->color()">
                            {{ $transfer->status->label() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $transfer->creator?->login ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $transfer->created_at?->format('d.m.Y H:i') ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $transfer->finished_at?->format('d.m.Y H:i') ?? '—' }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center text-zinc-500">
                        Brak transferów
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>