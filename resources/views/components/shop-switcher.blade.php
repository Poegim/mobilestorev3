<flux:dropdown position="bottom" align="start">

    <button
        type="button"
        class="flex w-full items-center gap-2.5 rounded-lg px-2 py-1.5 text-sm transition-colors text-left @if($currentShop) border-l-[6px] border-y border-r pl-[calc(0.5rem-4px)] @else hover:bg-zinc-100 dark:hover:bg-zinc-800 @endif"
        @if($currentShop)
            style="
                border-color: {{ $currentShop->color }};
                background: color-mix(in oklch, {{ $currentShop->color }} 30%, transparent);
            "
        @endif
    >
        <flux:avatar
            name="{{ $currentShop ? ($currentShop->short_name ?? $currentShop->name) : '' }}"
            :icon="$currentShop ? null : 'building-storefront'"
            size="sm"
            class="shrink-0"
        />

        <span class="flex-1 truncate font-semibold text-zinc-900 dark:text-zinc-100">
            {{ $currentShop ? ($currentShop->short_name ?? $currentShop->name) : 'Wszystkie sklepy' }}
        </span>

        <flux:icon.chevrons-up-down variant="micro" class="size-3.5 shrink-0 text-zinc-400" />
    </button>

    <flux:menu class="min-w-52">

        <flux:menu.item href="{{ route('dashboard') }}" wire:navigate>
            <span class="flex items-center gap-2.5 w-full">
                <flux:avatar icon="building-storefront" size="xs" />
                <span class="flex-1">Wszystkie sklepy</span>
                @if(!$currentShop)
                    <flux:icon.check variant="micro" class="size-3.5 text-zinc-400" />
                @endif
            </span>
        </flux:menu.item>

        <flux:separator />

        @foreach($shops as $shop)
            <flux:menu.item href="{{ route('shop.dashboard', $shop) }}" wire:navigate>
                <span class="flex items-center gap-2.5 w-full">
                    <flux:avatar name="{{ $shop->short_name ?? $shop->name }}" size="xs" class="shrink-0" />
                    <span class="flex-1 truncate @if($currentShop?->id === $shop->id) font-semibold @endif">
                        {{ $shop->short_name ?? $shop->name }}
                    </span>
                    @if($currentShop?->id === $shop->id)
                        <flux:icon.check variant="micro" class="size-3.5 shrink-0 text-zinc-400" />
                    @endif
                </span>
            </flux:menu.item>
        @endforeach

    </flux:menu>
</flux:dropdown>