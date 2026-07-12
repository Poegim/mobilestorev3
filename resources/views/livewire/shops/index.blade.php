<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Sklepy</flux:heading>
        <flux:button variant="primary" icon="plus" wire:click="create">Nowy sklep</flux:button>
    </div>

    <div class="flex items-center gap-4">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="Szukaj po nazwie, skrócie lub mieście..."
            class="max-w-sm"
        />
        <flux:switch wire:model.live="showArchived" label="Pokaż zarchiwizowane" />
    </div>

    <flux:table :paginate="$shops">
        <flux:table.columns>
            <flux:table.column>Sklep</flux:table.column>
            <flux:table.column>Adres</flux:table.column>
            <flux:table.column>Kontakt</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column />
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($shops as $shop)
                <flux:table.row :key="$shop->id">
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:avatar :name="$shop->name" src="{{ $shop->avatar_url }}" size="sm" />
                            <span class="size-2.5 shrink-0 rounded-full" style="background:{{ $shop->color }}"></span>
                            <div>
                                <div class="font-medium">{{ $shop->name }}</div>
                                <flux:text size="sm" variant="subtle">{{ $shop->short_name }}</flux:text>
                            </div>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $shop->address_street }} {{ $shop->address_building_number }}{{ $shop->address_apartment_number ? '/'.$shop->address_apartment_number : '' }},
                        {{ $shop->address_postal_code }} {{ $shop->address_city }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <div>{{ $shop->email }}</div>
                        <flux:text size="sm" variant="subtle">{{ $shop->phone }}</flux:text>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$shop->archive ? 'zinc' : 'green'" size="sm">
                            {{ $shop->archive ? 'Zarchiwizowany' : 'Aktywny' }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:dropdown position="bottom" align="end">
                            <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                            <flux:menu>
                                <flux:menu.item icon="pencil-square" wire:click="edit({{ $shop->id }})">Edytuj</flux:menu.item>
                                <flux:menu.item icon="archive-box" wire:click="toggleArchive({{ $shop->id }})">
                                    {{ $shop->archive ? 'Przywróć' : 'Archiwizuj' }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <flux:text variant="subtle">Brak sklepów.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Create / edit modal --}}
    <flux:modal wire:model.self="showForm" class="md:w-[32rem]">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Edytuj sklep' : 'Nowy sklep' }}</flux:heading>

            <flux:field>
                <flux:label>Zdjęcie sklepu</flux:label>
                <div class="flex items-center gap-4">
                    <flux:avatar :name="$name ?: '?'" src="{{ $this->avatarPreview }}" size="lg" />
                    <input type="file" wire:model="avatar" accept="image/*">
                </div>
                <flux:error name="avatar" />
            </flux:field>

            <flux:input wire:model="name" label="Nazwa" />
            <flux:input wire:model.live.debounce.500ms="short_name" label="Nazwa skrócona" required />

            <flux:field>
                <flux:label>Slug</flux:label>
                <flux:input wire:model.live.debounce.300ms="slug" />
                <flux:text size="sm" variant="subtle">Generuje się z nazwy skróconej — możesz nadpisać.</flux:text>
                <flux:error name="slug" />
            </flux:field>

            <flux:textarea wire:model="description" label="Opis" rows="2" />

            <flux:input wire:model="email" label="E-mail" type="email" />
            <flux:input wire:model="phone" label="Telefon" />

            <flux:field>
                <flux:label>Kolor w interfejsie</flux:label>
                <input type="color" wire:model="color">
                <flux:error name="color" />
            </flux:field>

            <flux:input wire:model="address_street" label="Ulica" />
            <flux:input wire:model="address_building_number" label="Nr budynku" />
            <flux:input wire:model="address_apartment_number" label="Nr lokalu (opcjonalnie)" />
            <flux:input wire:model="address_postal_code" label="Kod pocztowy" placeholder="00-000" />
            <flux:input wire:model="address_city" label="Miasto" />
            <flux:input wire:model="order" label="Kolejność" type="number" />

            <flux:checkbox.group wire:model="assignedUsers" label="Dostęp użytkowników">
                @foreach ($users as $user)
                    <flux:checkbox value="{{ $user->id }}" label="{{ $user->login }}" />
                @endforeach
            </flux:checkbox.group>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Anuluj</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Zapisz</flux:button>
            </div>
        </form>
    </flux:modal>
</div>