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

<div class="space-y-3 in-data-flux-sidebar-collapsed-desktop:space-y-0">

    {{-- ===== KARTA AKTYWNEGO SKLEPU (statyczna) ===== --}}
    <div
        class="flex items-center gap-3 rounded-xl p-3
               bg-zinc-50 ring-1 ring-zinc-200/70
               dark:bg-zinc-900 dark:ring-zinc-800
               in-data-flux-sidebar-collapsed-desktop:hidden"
        @if($currentShop)
            style="
                background: color-mix(in oklch, {{ $currentShop->color }} 10%, transparent);
                --tw-ring-color: color-mix(in oklch, {{ $currentShop->color }} 30%, transparent);
            "
        @endif
    >
        @if($currentShop)
            <span
                class="flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white
                       ring-2 ring-white/60 dark:ring-zinc-950/60"
                style="background: {{ $currentShop->color }};"
            >
                {{ mb_substr($label, 0, 1) }}
            </span>
        @else
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-700">
                <flux:icon.building-storefront variant="mini" class="size-5 text-zinc-500 dark:text-zinc-300" />
            </span>
        @endif

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $label }}
            </p>
        </div>
    </div>

    {{-- ===== SELECT (dropdown) ===== --}}
    <flux:dropdown position="bottom" align="start" class="w-full">
        <button
            type="button"
            class="group flex w-full items-center gap-2.5 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-left transition
                   hover:bg-zinc-50 hover:border-zinc-300
                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-300
                   dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800 dark:hover:border-zinc-600 dark:focus-visible:ring-zinc-600
                   in-data-flux-sidebar-collapsed-desktop:justify-center in-data-flux-sidebar-collapsed-desktop:gap-0 in-data-flux-sidebar-collapsed-desktop:px-0 in-data-flux-sidebar-collapsed-desktop:py-2.5"
        >
            {{-- Wskaźnik aktywnego sklepu (w collapsed to jedyny widoczny element) --}}
            @if($currentShop)
                <span
                    class="size-2.5 shrink-0 rounded-full in-data-flux-sidebar-collapsed-desktop:size-3"
                    style="background: {{ $currentShop->color }};"
                ></span>
            @else
                <flux:icon.building-storefront variant="micro" class="size-3.5 shrink-0 text-zinc-400" />
            @endif

            <flux:icon.arrow-path variant="micro" class="size-3.5 shrink-0 text-zinc-400 in-data-flux-sidebar-collapsed-desktop:hidden" />

            <span class="flex-1 truncate font-medium text-zinc-600 dark:text-zinc-400 in-data-flux-sidebar-collapsed-desktop:hidden">
                Zmień sklep
            </span>

            <flux:icon.chevron-down variant="micro"
                class="size-3.5 shrink-0 text-zinc-400 transition-transform group-data-open:rotate-180 in-data-flux-sidebar-collapsed-desktop:hidden" />
        </button>

        <flux:menu class="min-w-60 p-1.5">
            <flux:menu.heading class="px-2.5 pt-1 pb-1 text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                Przełącz sklep
            </flux:menu.heading>

            <flux:menu.item
                href="{{ $urlFor() }}"
                wire:navigate
                @class(['flex! items-center! gap-2.5! rounded-lg! px-2.5!' => true, 'bg-zinc-100 dark:bg-zinc-800' => ! $currentShop])
            >
                <span class="flex size-6 shrink-0 items-center justify-center rounded-md bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon.building-storefront variant="micro" class="size-3.5 text-zinc-500 dark:text-zinc-400" />
                </span>
                <span class="flex-1 truncate @if(! $currentShop) font-semibold @endif">Wszystkie sklepy</span>
                @if(! $currentShop)
                    <flux:icon.check variant="micro" class="size-4 shrink-0 text-zinc-500" />
                @endif
            </flux:menu.item>

            <flux:separator class="my-1!" />

            @foreach($shops as $shop)
                @php($active = $currentShop?->id === $shop->id)
                <flux:menu.item
                    href="{{ $urlFor($shop) }}"
                    wire:navigate
                    @class(['flex! items-center! gap-2.5! rounded-lg! px-2.5!' => true, 'bg-zinc-100 dark:bg-zinc-800' => $active])
                >
                    <span
                        class="flex size-6 shrink-0 items-center justify-center rounded-md text-[10px] font-bold text-white"
                        style="background: {{ $shop->color }};"
                    >
                        {{ mb_substr($shop->short_name ?? $shop->name, 0, 1) }}
                    </span>
                    <span class="flex-1 truncate @if($active) font-semibold @endif">
                        {{ $shop->short_name ?? $shop->name }}
                    </span>
                    @if($active)
                        <flux:icon.check variant="micro" class="size-4 shrink-0" style="color: {{ $shop->color }};" />
                    @endif
                </flux:menu.item>
            @endforeach
        </flux:menu>
    </flux:dropdown>

</div>