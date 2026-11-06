@php
    $label = $currentShop ? ($currentShop->short_name ?? $currentShop->name) : 'Wszystkie sklepy';

    // Whitelist of modules that exist both globally and per shop.
    // Admin pages and detail routes fall back to the dashboard.
    $module = collect(['items', 'sells', 'purchases', 'transfers'])
        ->first(fn ($m) => request()->routeIs("*{$m}.*"));

    // Build the URL of the same module for a given shop (null = all shops).
    // Falls back to the dashboard when the target route does not exist.
    $urlFor = function ($shop = null) use ($module) {
        $name = ($shop ? 'shop.' : '') . ($module ? "{$module}.index" : 'dashboard');

        if (! \Illuminate\Support\Facades\Route::has($name)) {
            $name = $shop ? 'shop.dashboard' : 'dashboard';
        }

        return $shop ? route($name, $shop) : route($name);
    };
@endphp

<flux:dropdown position="bottom" align="start">

    <button
        type="button"
        title="{{ $label }}"
        class="flex w-full items-center gap-2.5 rounded-lg px-2 py-1.5 text-sm transition-colors text-left hover:bg-zinc-100 dark:hover:bg-zinc-800 in-data-flux-sidebar-collapsed-desktop:justify-center in-data-flux-sidebar-collapsed-desktop:gap-0 in-data-flux-sidebar-collapsed-desktop:px-0"
        @if($currentShop)
            style="
                background: color-mix(in oklch, {{ $currentShop->color }} 20%, transparent);
                box-shadow: inset 0 -3px 0 0 {{ $currentShop->color }};
            "
        @endif
    >
        <flux:avatar
            name="{{ $currentShop ? $label : '' }}"
            :icon="$currentShop ? null : 'building-storefront'"
            size="sm"
            class="shrink-0"
        />

        <span class="flex-1 truncate font-semibold text-zinc-900 dark:text-zinc-100 in-data-flux-sidebar-collapsed-desktop:hidden">
            {{ $label }}
        </span>

        <flux:icon.chevrons-up-down
            variant="micro"
            class="size-3.5 shrink-0 text-zinc-400 in-data-flux-sidebar-collapsed-desktop:hidden"
        />
    </button>

        <flux:menu class="min-w-52">

            <flux:menu.item href="{{ $urlFor() }}" wire:navigate>
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
                <flux:menu.item href="{{ $urlFor($shop) }}" wire:navigate>
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