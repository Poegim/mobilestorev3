<div>
    <h1 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Create Sell</h1>
    <p class="text-zinc-600 dark:text-zinc-400">Shop: {{ $shop->name }}</p>

    <div class="mb-4 flex items-end gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 px-4 py-3">
        <div class="w-32">
            <flux:field>
                <flux:label>Wprowadź ID</flux:label>
                <flux:input wire:model="id" size="sm" />
                <flux:error name="id" />
            </flux:field>
        </div>

        <flux:button size="sm" variant="primary">
            Add
        </flux:button>
    </div>

    <div class="mb-4 flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 px-4 py-3">
        <span class="text-sm text-zinc-600 dark:text-zinc-400">Przedmiotów:</span>
        <span class="text-sm text-zinc-600 dark:text-zinc-400">Netto:</span>
        <span class="text-sm text-zinc-600 dark:text-zinc-400">Brutto:</span>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm text-left">
            <thead class="bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
                <tr>
                    <th class="px-4 py-3 font-medium">ID</th>
                    <th class="px-4 py-3 font-medium">Item</th>
                    <th class="px-4 py-3 font-medium text-right">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($items as $item)
                    <tr class="text-zinc-700 dark:text-zinc-300">
                        <td class="px-4 py-3">{{ $item->id }}</td>
                        <td class="px-4 py-3">{{ $item->name }}</td>
                        <td class="px-4 py-3 text-right">
                            <flux:button size="xs" variant="danger" wire:click="removeItem({{ $item->id }})">
                                Remove
                            </flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-zinc-500">
                            No items added yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>