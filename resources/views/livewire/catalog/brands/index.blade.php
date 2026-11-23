<div class="space-y-6">
    {{-- Header: title on the left, search and action on the right --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl" level="1">Marki</flux:heading>

        <div class="flex items-center gap-2">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Szukaj marki..." />
            <flux:button wire:click="create" variant="primary" icon="plus">Nowa marka</flux:button>
        </div>
    </div>

    <flux:table :paginate="$brands">
        <flux:table.columns>
            <flux:table.column>ID</flux:table.column>
            <flux:table.column>Nazwa</flux:table.column>
            <flux:table.column>Produktów</flux:table.column>
            <flux:table.column>Utworzono</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($brands as $brand)
                <flux:table.row :key="$brand->id">
                    <flux:table.cell class="text-zinc-400 text-xs tabular-nums">{{ $brand->id }}</flux:table.cell>
                    <flux:table.cell variant="strong">
                        <flux:link :href="route('catalog.brands.show', $brand)" wire:navigate>{{ $brand->name }}</flux:link>
                    </flux:table.cell>
                    <flux:table.cell class="tabular-nums">{{ $brand->products_count }}</flux:table.cell>
                    <flux:table.cell class="text-zinc-500 text-sm">{{ $brand->created_at?->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-1">
                            <flux:button wire:click="edit({{ $brand->id }})" size="sm" variant="ghost" icon="pencil-square" />
                            @if($brand->products_count === 0)
                                <flux:button
                                    wire:click="confirmDelete({{ $brand->id }})"
                                    size="sm"
                                    variant="danger"
                                    icon="trash"
                                />
                            @else
                                <flux:tooltip content="Nie można usunąć — marka ma {{ $brand->products_count }} produktów">
                                    <flux:icon.lock-closed class="size-4 text-zinc-300 dark:text-zinc-600" />
                                </flux:tooltip>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <div class="py-8 text-center">
                            <flux:icon.tag class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                            <flux:text class="mt-2">
                                @if($search)
                                    Brak marek pasujących do "{{ $search }}"
                                @else
                                    Nie dodano jeszcze żadnej marki.
                                @endif
                            </flux:text>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Create / edit modal --}}
    <flux:modal name="brand-form" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $brandId ? 'Edytuj markę' : 'Nowa marka' }}</flux:heading>

            <flux:input wire:model="name" label="Nazwa" autofocus />

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Anuluj</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Zapisz</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete confirmation modal --}}
    <flux:modal name="brand-delete" class="md:w-96">
        <div class="space-y-6">
            <flux:heading size="lg">Usunąć markę?</flux:heading>
            <flux:text>Ta operacja jest nieodwracalna.</flux:text>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Anuluj</flux:button>
                </flux:modal.close>
                <flux:button wire:click="delete" variant="danger">Usuń</flux:button>
            </div>
        </div>
    </flux:modal>
</div>