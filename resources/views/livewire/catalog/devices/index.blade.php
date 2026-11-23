{{-- resources/views/livewire/catalog/devices/index.blade.php --}}
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl" level="1">Urządzenia</flux:heading>

        <div class="flex items-center gap-2">
            <flux:select wire:model.live="brandFilter" class="w-44">
                <option value="">Wszystkie marki</option>
                @foreach($this->brands as $brand)
                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                @endforeach
            </flux:select>
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Szukaj urządzenia..." />
            <flux:button wire:click="create" variant="primary" icon="plus">Nowe urządzenie</flux:button>
        </div>
    </div>

    <flux:table :paginate="$devices">
        <flux:table.columns>
            <flux:table.column>ID</flux:table.column>
            <flux:table.column>Marka</flux:table.column>
            <flux:table.column>Nazwa</flux:table.column>
            <flux:table.column>Wariantów</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($devices as $device)
                <flux:table.row :key="$device->id">
                    <flux:table.cell class="text-zinc-400 text-xs tabular-nums">{{ $device->id }}</flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $device->brand->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell variant="strong">{{ $device->model }}</flux:table.cell>
                    <flux:table.cell class="tabular-nums">{{ $device->variants_count }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-1">
                            <flux:button wire:click="edit({{ $device->id }})" size="sm" variant="ghost" icon="pencil-square" />
                            @if($device->variants_count === 0)
                                <flux:button wire:click="confirmDelete({{ $device->id }})" size="sm" variant="danger" icon="trash" />
                            @else
                                <flux:tooltip content="Nie można usunąć — urządzenie ma {{ $device->variants_count }} wariantów">
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
                            <flux:icon.device-phone-mobile class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                            <flux:text class="mt-2">
                                @if($search || $brandFilter)
                                    Brak urządzeń pasujących do filtrów.
                                @else
                                    Nie dodano jeszcze żadnego urządzenia.
                                @endif
                            </flux:text>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Create / edit modal --}}
    <flux:modal name="device-form" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $deviceId ? 'Edytuj urządzenie' : 'Nowe urządzenie' }}</flux:heading>

            <flux:select wire:model="brand_id" label="Marka">
                <option value="">Wybierz markę...</option>
                @foreach($this->brands as $brand)
                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="model" label="Model" placeholder="np. iPhone 11, Galaxy S24..." autofocus />

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
    <flux:modal name="device-delete" class="md:w-96">
        <div class="space-y-6">
            <flux:heading size="lg">Usunąć urządzenie?</flux:heading>
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