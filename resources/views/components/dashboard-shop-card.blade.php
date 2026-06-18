@props([
    'shopName',
    'shopColor' => '#000000',
    'stock' => 0,
    'today' => [],
    'month' => [],
])

{{-- Per-shop quantity stats card --}}
<div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-3">

    {{-- Shop header --}}
    <div class="flex items-center gap-2 mb-3">
        <span class="size-2.5 rounded-full shrink-0" style="background-color: {{ $shopColor }}"></span>
        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $shopName }}</span>
        <span class="ml-auto text-xs text-zinc-400 dark:text-zinc-500">{{ $stock }} na stanie</span>
    </div>

    {{-- Two-column: today + month --}}
    <div class="grid grid-cols-2 gap-4">

        {{-- Today --}}
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 uppercase tracking-wide mb-1.5">Dziś</p>
            <div class="text-lg font-medium text-zinc-900 dark:text-zinc-100 leading-none">
                {{ $today['transactions'] ?? 0 }}
                <span class="text-xs font-normal text-zinc-400 dark:text-zinc-500">{{ ($today['transactions'] ?? 0) === 1 ? 'transakcja' : 'transakcji' }}</span>
            </div>
            <div class="mt-2 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                <span>
                    <flux:icon name="device-phone-mobile" variant="micro" class="size-3 inline -mt-px" />
                    {{ $today['devices'] ?? 0 }}
                </span>
                <span>
                    <flux:icon name="shopping-bag" variant="micro" class="size-3 inline -mt-px" />
                    {{ $today['accessories'] ?? 0 }}
                </span>
                <span>
                    <flux:icon name="wrench-screwdriver" variant="micro" class="size-3 inline -mt-px" />
                    {{ $today['services'] ?? 0 }}
                </span>
            </div>
        </div>

        {{-- Month --}}
        <div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 uppercase tracking-wide mb-1.5">Miesiąc</p>
            <div class="text-lg font-medium text-zinc-900 dark:text-zinc-100 leading-none">
                {{ $month['transactions'] ?? 0 }}
                <span class="text-xs font-normal text-zinc-400 dark:text-zinc-500">transakcji</span>
            </div>
            <div class="mt-2 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                <span>
                    <flux:icon name="device-phone-mobile" variant="micro" class="size-3 inline -mt-px" />
                    {{ $month['devices'] ?? 0 }}
                </span>
                <span>
                    <flux:icon name="shopping-bag" variant="micro" class="size-3 inline -mt-px" />
                    {{ $month['accessories'] ?? 0 }}
                </span>
                <span>
                    <flux:icon name="wrench-screwdriver" variant="micro" class="size-3 inline -mt-px" />
                    {{ $month['services'] ?? 0 }}
                </span>
            </div>
        </div>

    </div>
</div>