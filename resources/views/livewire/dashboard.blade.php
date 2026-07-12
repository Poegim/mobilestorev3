<div>
    {{-- Header: date + shop name --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between mb-6">
        <div>
            <flux:text class="text-xs uppercase tracking-wide">
                {{ now()->translatedFormat('l, j F Y') }}
            </flux:text>
            <flux:heading size="xl">
                {{ $shop ? $shop->name : 'Wszystkie sklepy' }}
            </flux:heading>
        </div>
    </div>

    {{-- Period switcher + loading spinner --}}
    <div class="mb-4 flex flex-wrap items-center gap-1">
        @foreach([
            'today'       => 'Dziś',
            'yesterday'   => 'Wczoraj',
            'week'        => 'Tydzień',
            'month'       => 'Miesiąc',
            'last30'      => '30 dni',
            'last60'      => '60 dni',
            'last90'      => '90 dni',
            'quarter'     => 'Kwartał',
            'lastquarter' => 'Poprz. kwartał',
        ] as $value => $label)
            <button
                wire:click="$set('period', '{{ $value }}')"
                @class([
                    'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors duration-150',
                    'bg-indigo-600 text-white shadow-sm' => $period === $value,
                    'text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800' => $period !== $value,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
        <flux:icon
            name="arrow-path"
            wire:loading
            wire:target="period"
            class="size-4 text-indigo-400 animate-spin"
        />
    </div>

    {{-- Lazy module quick-load buttons — compact row, visible when modules not yet loaded --}}
    @if(($isAdmin && !$revenueLoaded) || !$shopStatsLoaded || !$topProductsLoaded)
        <div class="flex flex-wrap gap-2 mb-6">
            @if($isAdmin && !$revenueLoaded)
                <flux:button
                    wire:click="loadRevenueStats"
                    variant="outline"
                    size="sm"
                    icon="chart-bar"
                    wire:loading.attr="disabled"
                    wire:target="loadRevenueStats"
                >
                    <span wire:loading.remove wire:target="loadRevenueStats">Obroty i kategorie</span>
                    <span wire:loading wire:target="loadRevenueStats">Ładowanie…</span>
                </flux:button>
            @endif

            @if(!$shopStatsLoaded)
                <flux:button
                    wire:click="loadShopStats"
                    variant="outline"
                    size="sm"
                    icon="building-storefront"
                    wire:loading.attr="disabled"
                    wire:target="loadShopStats"
                >
                    <span wire:loading.remove wire:target="loadShopStats">Statystyki sklepów</span>
                    <span wire:loading wire:target="loadShopStats">Ładowanie…</span>
                </flux:button>
            @endif

            @if(!$topProductsLoaded)
                <flux:button
                    wire:click="loadTopProducts"
                    variant="outline"
                    size="sm"
                    icon="arrow-trending-up"
                >
                    Trendy
                </flux:button>
            @endif
        </div>
    @endif

    {{-- Everything below gets an overlay while period is loading --}}
    <div class="relative">

        {{-- Loading overlay --}}
        <div
            wire:loading
            wire:target="period"
            class="absolute inset-0 z-10 rounded-xl bg-zinc-950/40 backdrop-blur-[2px] flex items-center justify-center"
        >
            <div class="flex items-center gap-2 bg-zinc-800 text-zinc-100 px-4 py-2.5 rounded-full text-sm shadow-xl">
                <flux:icon name="arrow-path" class="size-4 animate-spin text-indigo-400" />
                Ładowanie danych…
            </div>
        </div>

        {{-- ===== INSTANT: lightweight counts (no joins) ===== --}}
        <div class="grid gap-3 grid-cols-2 lg:grid-cols-4 mb-6">
            <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/60 px-4 py-3">
                <flux:text class="text-xs uppercase tracking-wide">Sprzedaż — {{ $this->periodLabel }}</flux:text>
                <div class="mt-1 text-2xl font-medium text-zinc-900 dark:text-zinc-50 leading-none">
                    {{ $todaySells }}
                </div>
                <flux:text class="text-xs mt-0.5">{{ $todaySells === 1 ? 'transakcja' : 'transakcji' }}</flux:text>
            </div>

            <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/60 px-4 py-3">
                <flux:text class="text-xs uppercase tracking-wide">Na magazynie</flux:text>
                <div class="mt-1 text-2xl font-medium text-zinc-900 dark:text-zinc-50 leading-none">
                    {{ number_format($itemsInStock, 0, ',', ' ') }}
                </div>
                <flux:text class="text-xs mt-0.5">przedmiotów</flux:text>
            </div>

            <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/60 px-4 py-3">
                <flux:text class="text-xs uppercase tracking-wide">Zakupy dziś</flux:text>
                <div class="mt-1 text-2xl font-medium text-zinc-900 dark:text-zinc-50 leading-none">
                    {{ $todayPurchases }}
                </div>
                <flux:text class="text-xs mt-0.5">przyjęć towaru</flux:text>
            </div>

            @if($isAdmin && $revenueLoaded)
                <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/60 px-4 py-3">
                    <flux:text class="text-xs uppercase tracking-wide">{{ now()->translatedFormat('F') }}</flux:text>
                    <div class="mt-1 text-2xl font-medium text-zinc-900 dark:text-zinc-50 leading-none">
                        {{ number_format($monthRevenue / 100, 2, ',', ' ') }} zł
                    </div>
                    <flux:text class="text-xs mt-0.5">{{ $monthSells }} transakcji</flux:text>
                </div>
            @else
                <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/60 px-4 py-3 opacity-40">
                    <flux:text class="text-xs uppercase tracking-wide">{{ now()->translatedFormat('F') }}</flux:text>
                    <div class="mt-1 text-2xl font-medium text-zinc-900 dark:text-zinc-50 leading-none">— zł</div>
                    <flux:text class="text-xs mt-0.5">—</flux:text>
                </div>
            @endif
        </div>

        {{-- ===== REVENUE MODULE ===== --}}
        @if($isAdmin && $revenueLoaded)
            <div class="mb-6">
                <flux:heading size="lg" class="mb-3">Obroty i kategorie</flux:heading>
                <div class="grid gap-4 lg:grid-cols-[1fr_280px]">

                    <flux:card>
                        <flux:text class="text-xs uppercase tracking-wide">Obrót — {{ $this->periodLabel }}</flux:text>
                        <div class="mt-1 text-4xl font-medium text-zinc-900 dark:text-zinc-50 leading-none">
                            {{ number_format($todayRevenue / 100, 2, ',', ' ') }} zł
                        </div>
                        <flux:text class="mt-1 text-sm">
                            {{ $todaySells }} {{ $todaySells === 1 ? 'transakcja' : 'transakcji' }}
                        </flux:text>

                        @php
                            $totalRevenue = max(
                                ($todayCategories['devices']['revenue'] ?? 0)
                                + ($todayCategories['accessories']['revenue'] ?? 0)
                                + ($todayCategories['services']['revenue'] ?? 0),
                                1
                            );
                        @endphp

                        <div class="mt-6 space-y-4">
                            <x-dashboard-category-bar
                                icon="device-phone-mobile"
                                label="Urządzenia"
                                :revenue="$todayCategories['devices']['revenue'] ?? 0"
                                :count="$todayCategories['devices']['count'] ?? 0"
                                :profit="$todayCategories['devices']['profit'] ?? null"
                                :percentage="round(($todayCategories['devices']['revenue'] ?? 0) / $totalRevenue * 100)"
                                :total-profit="$todayCategories['totalProfit'] ?? null"
                                color="bg-purple-500 dark:bg-purple-400"
                            />
                            <x-dashboard-category-bar
                                icon="shopping-bag"
                                label="Akcesoria"
                                :revenue="$todayCategories['accessories']['revenue'] ?? 0"
                                :count="$todayCategories['accessories']['count'] ?? 0"
                                :profit="$todayCategories['accessories']['profit'] ?? null"
                                :percentage="round(($todayCategories['accessories']['revenue'] ?? 0) / $totalRevenue * 100)"
                                :total-profit="$todayCategories['totalProfit'] ?? null"
                                color="bg-teal-500 dark:bg-teal-400"
                            />
                            <x-dashboard-category-bar
                                icon="wrench-screwdriver"
                                label="Usługi"
                                :revenue="$todayCategories['services']['revenue'] ?? 0"
                                :count="$todayCategories['services']['count'] ?? 0"
                                :profit="$todayCategories['services']['profit'] ?? null"
                                :percentage="round(($todayCategories['services']['revenue'] ?? 0) / $totalRevenue * 100)"
                                :total-profit="$todayCategories['totalProfit'] ?? null"
                                color="bg-orange-500 dark:bg-orange-400"
                            />
                        </div>

                        @if(isset($todayCategories['totalProfit']))
                            <div class="mt-5 pt-4 border-t border-zinc-200 dark:border-zinc-700 flex items-baseline justify-between">
                                <flux:text class="text-sm">Zysk łączny — {{ $this->periodLabel }}</flux:text>
                                <span class="text-lg font-medium {{ $todayCategories['totalProfit'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $todayCategories['totalProfit'] >= 0 ? '+' : '' }}{{ number_format($todayCategories['totalProfit'] / 100, 2, ',', ' ') }} zł
                                </span>
                            </div>
                        @endif
                    </flux:card>

                    <div class="flex flex-col gap-4">
                        <flux:card>
                            <flux:text class="text-xs uppercase tracking-wide">{{ now()->translatedFormat('F') }}</flux:text>
                            <div class="mt-1 text-2xl font-medium text-zinc-900 dark:text-zinc-50 leading-none">
                                {{ number_format($monthRevenue / 100, 2, ',', ' ') }} zł
                            </div>
                            <flux:text class="mt-1 text-sm">{{ $monthSells }} transakcji</flux:text>

                            <div class="mt-4 space-y-2">
                                <x-dashboard-category-row icon="device-phone-mobile" label="Urządzenia" :revenue="$monthCategories['devices']['revenue'] ?? 0" />
                                <x-dashboard-category-row icon="shopping-bag" label="Akcesoria" :revenue="$monthCategories['accessories']['revenue'] ?? 0" />
                                <x-dashboard-category-row icon="wrench-screwdriver" label="Usługi" :revenue="$monthCategories['services']['revenue'] ?? 0" />
                            </div>

                            @if(isset($monthCategories['totalProfit']))
                                <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700 flex items-baseline justify-between">
                                    <flux:text class="text-xs">Zysk</flux:text>
                                    <span class="text-base font-medium {{ $monthCategories['totalProfit'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $monthCategories['totalProfit'] >= 0 ? '+' : '' }}{{ number_format($monthCategories['totalProfit'] / 100, 2, ',', ' ') }} zł
                                    </span>
                                </div>
                            @endif
                        </flux:card>
                    </div>
                </div>
            </div>
        @endif

        {{-- ===== SHOP STATS MODULE ===== --}}
        @if($shopStatsLoaded)
            <div class="mb-6">
                @if($isAdmin && !$shop)
                    <flux:heading size="lg" class="mb-3">Sklepy — ilości</flux:heading>
                @endif
                <div class="grid gap-3 {{ $shop ? '' : 'sm:grid-cols-2' }}">
                    @foreach($shopStats as $stat)
                        <x-dashboard-shop-card
                            :shop-name="$stat['shopName']"
                            :shop-color="$stat['shopColor']"
                            :rank="$stat['rank']"
                            :revenue="$stat['revenue']"
                            :transactions="$stat['transactions']"
                            :max-transactions="$stat['maxTransactions']"
                            :devices="$stat['devices']"
                            :accessories="$stat['accessories']"
                            :services="$stat['services']"
                        />
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ===== TOP PRODUCTS MODULE ===== --}}
        @if($topProductsLoaded)
            <div class="mt-6">
                <flux:heading size="lg" class="mb-3">Top produkty i trendy</flux:heading>
                <livewire:top-products :shop="$shop" />
            </div>
        @endif

    </div>{{-- end relative wrapper --}}
</div>