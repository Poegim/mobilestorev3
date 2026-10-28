<div class="space-y-6">
    {{-- Header: title on the left, search and action on the right --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl" level="1">Brands</flux:heading>

        <div class="flex items-center gap-2">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search brands..." />
            <flux:button wire:click="create" variant="primary" icon="plus">New brand</flux:button>
        </div>
    </div>

    <flux:table :paginate="$brands">
        <flux:table.columns>
            <flux:table.column>ID</flux:table.column>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Created</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($brands as $brand)
                <flux:table.row :key="$brand->id">
                    <flux:table.cell>{{ $brand->id }}</flux:table.cell>
                    <flux:table.cell>{{ $brand->name }}</flux:table.cell>
                    <flux:table.cell>{{ $brand->created_at?->format('Y-m-d H:i') }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button wire:click="edit({{ $brand->id }})" size="sm" icon="pencil-square" />
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    {{-- Create / edit modal --}}
    <flux:modal name="brand-form" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $brandId ? 'Edit brand' : 'New brand' }}</flux:heading>

            <flux:input wire:model="name" label="Name" autofocus />

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>