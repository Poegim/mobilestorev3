<div>
    <div class="mb-4 flex items-center justify-between">
        <flux:heading size="xl">Kontakty</flux:heading>

        <div class="flex items-center gap-3">
            <flux:badge size="lg" color="zinc">{{ $contacts->total() }}</flux:badge>
            <flux:button variant="primary" icon="plus" wire:click="create">Nowy kontakt</flux:button>
        </div>
    </div>

    <div class="mb-4 flex gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Szukaj: nazwa, NIP, e-mail, telefon, miasto..."
            icon="magnifying-glass"
            clearable
        />

        <flux:select wire:model.live="perPage" class="w-24">
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </flux:select>
    </div>

    <flux:table :paginate="$contacts">
        <flux:table.columns>
            <flux:table.column>ID</flux:table.column>
            <flux:table.column>Nazwa</flux:table.column>
            <flux:table.column>NIP</flux:table.column>
            <flux:table.column>E-mail</flux:table.column>
            <flux:table.column>Telefon</flux:table.column>
            <flux:table.column>Adres</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($contacts as $contact)
                <flux:table.row wire:key="contact-{{ $contact->id }}">
                    <flux:table.cell variant="strong">{{ $contact->id }}</flux:table.cell>
                    <flux:table.cell variant="strong">{{ $contact->name ?: '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $contact->identity_number ?: '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $contact->email ?: '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $contact->phone ?: '—' }}</flux:table.cell>
                    <flux:table.cell>
                        {{ collect([$contact->street, trim($contact->postal_code.' '.$contact->city)])->filter()->implode(', ') ?: '—' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $contact->id }})" />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center text-zinc-500">
                        Brak kontaktów
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="contact-form" class="md:w-120">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Edytuj kontakt' : 'Nowy kontakt' }}</flux:heading>

            <flux:input wire:model="name" label="Nazwa" required />
            <flux:input wire:model="identity_number" label="NIP" />
            <flux:input wire:model="email" label="E-mail" type="email" />
            <flux:input wire:model="phone" label="Telefon" />
            <flux:input wire:model="street" label="Ulica i numer" />
            <flux:input wire:model="postal_code" label="Kod pocztowy" placeholder="00-000" />
            <flux:input wire:model="city" label="Miasto" />
            <flux:textarea wire:model="notes" label="Notatki" rows="3" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Anuluj</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Zapisz</flux:button>
            </div>
        </form>
    </flux:modal>
</div>