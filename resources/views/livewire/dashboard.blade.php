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

    {{-- ===== INSTANT: lightweight counts (no joins) ===== --}}
    <div class="grid gap-3 grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/60 px-4 py-3">
            <flux:text class="text-xs uppercase tracking-wide">Sprzedaż dziś</flux:text>
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
                <div class="mt-1 text-2xl font-medium text-zinc-900 dark:text-zinc-50 leading-none">
                    — zł
                </div>
                <flux:text class="text-xs mt-0.5">wczytaj poniżej</flux:text>
            </div>
        @endif
    </div>

    @if($isAdmin)
        {{-- ===== LAZY MODULE: Revenue breakdown + categories ===== --}}
        <x-dashboard-lazy-module
            :loaded="$revenueLoaded"
            action="loadRevenueStats"
            label="Wczytaj obroty i zyski"
            icon="chart-bar"
            height="min-h-[260px]"
            title="Obroty i kategorie"
            class="mb-6"
        >
            <x-slot:placeholder>
                {{-- Fake blurred content so the placeholder looks like something is there --}}
                <div class="grid gap-4 lg:grid-cols-[1fr_280px]">
                    <div class="space-y-3">
                        <div class="h-10 w-48 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                        <div class="h-4 w-full rounded bg-zinc-200 dark:bg-zinc-700"></div>
                        <div class="h-4 w-3/4 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                        <div class="h-4 w-1/2 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                    </div>
                    <div class="space-y-3">
                        <div class="h-6 w-32 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                        <div class="h-4 w-full rounded bg-zinc-200 dark:bg-zinc-700"></div>
                        <div class="h-4 w-full rounded bg-zinc-200 dark:bg-zinc-700"></div>
                    </div>
                </div>
            </x-slot:placeholder>

            {{-- Actual revenue content (rendered only when loaded) --}}
            <div class="grid gap-4 lg:grid-cols-[1fr_280px]">

                {{-- LEFT — Today's revenue scorecard --}}
                <flux:card>
                    <flux:text class="text-xs uppercase tracking-wide">Obrót dziś</flux:text>
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
                            color="bg-purple-500 dark:bg-purple-400"
                        />
                        <x-dashboard-category-bar
                            icon="shopping-bag"
                            label="Akcesoria"
                            :revenue="$todayCategories['accessories']['revenue'] ?? 0"
                            :count="$todayCategories['accessories']['count'] ?? 0"
                            :profit="$todayCategories['accessories']['profit'] ?? null"
                            :percentage="round(($todayCategories['accessories']['revenue'] ?? 0) / $totalRevenue * 100)"
                            color="bg-teal-500 dark:bg-teal-400"
                        />
                        <x-dashboard-category-bar
                            icon="wrench-screwdriver"
                            label="Usługi"
                            :revenue="$todayCategories['services']['revenue'] ?? 0"
                            :count="$todayCategories['services']['count'] ?? 0"
                            :profit="$todayCategories['services']['profit'] ?? null"
                            :percentage="round(($todayCategories['services']['revenue'] ?? 0) / $totalRevenue * 100)"
                            color="bg-orange-500 dark:bg-orange-400"
                        />
                    </div>

                    @if(isset($todayCategories['totalProfit']))
                        <div class="mt-5 pt-4 border-t border-zinc-200 dark:border-zinc-700 flex items-baseline justify-between">
                            <flux:text class="text-sm">Zysk łączny dziś</flux:text>
                            <span class="text-lg font-medium {{ $todayCategories['totalProfit'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $todayCategories['totalProfit'] >= 0 ? '+' : '' }}{{ number_format($todayCategories['totalProfit'] / 100, 2, ',', ' ') }} zł
                            </span>
                        </div>
                    @endif
                </flux:card>

                {{-- RIGHT — Month summary --}}
                <div class="flex flex-col gap-4">
                    <flux:card>
                        <flux:text class="text-xs uppercase tracking-wide">
                            {{ now()->translatedFormat('F') }}
                        </flux:text>
                        <div class="mt-1 text-2xl font-medium text-zinc-900 dark:text-zinc-50 leading-none">
                            {{ number_format($monthRevenue / 100, 2, ',', ' ') }} zł
                        </div>
                        <flux:text class="mt-1 text-sm">{{ $monthSells }} transakcji</flux:text>

                        <div class="mt-4 space-y-2">
                            <x-dashboard-category-row
                                icon="device-phone-mobile"
                                label="Urządzenia"
                                :revenue="$monthCategories['devices']['revenue'] ?? 0"
                            />
                            <x-dashboard-category-row
                                icon="shopping-bag"
                                label="Akcesoria"
                                :revenue="$monthCategories['accessories']['revenue'] ?? 0"
                            />
                            <x-dashboard-category-row
                                icon="wrench-screwdriver"
                                label="Usługi"
                                :revenue="$monthCategories['services']['revenue'] ?? 0"
                            />
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
        </x-dashboard-lazy-module>
    @endif

    {{-- ===== LAZY MODULE: Per-shop stats ===== --}}
    <x-dashboard-lazy-module
        :loaded="$shopStatsLoaded"
        action="loadShopStats"
        label="Wczytaj statystyki sklepów"
        icon="building-storefront"
        height="min-h-[160px]"
        :title="$isAdmin && !$shop ? 'Sklepy — ilości' : null"
        class="mb-6"
    >
        <x-slot:placeholder>
            <div class="grid gap-3 sm:grid-cols-2">
                @for($i = 0; $i < 4; $i++)
                    <div class="h-20 rounded-lg bg-zinc-200 dark:bg-zinc-700"></div>
                @endfor
            </div>
        </x-slot:placeholder>

        <div class="grid gap-3 {{ $shop ? '' : 'sm:grid-cols-2' }}">
            @foreach($shopStats as $stat)
                <x-dashboard-shop-card
                    :shop-name="$stat['shopName']"
                    :shop-color="$stat['shopColor']"
                    :stock="$stat['stock']"
                    :today="$stat['today']"
                    :month="$stat['month']"
                />
            @endforeach
        </div>
    </x-dashboard-lazy-module>

    {{-- ===== LAZY MODULE: Top Products (separate Livewire component) ===== --}}
    @if($topProductsLoaded)
        <div class="mt-6">
            <flux:heading size="lg" class="mb-3">Top produkty i trendy</flux:heading>
            <livewire:top-products :shop="$shop" />
        </div>
    @else
        <x-dashboard-lazy-module
            :loaded="false"
            action="loadTopProducts"
            label="Wczytaj trendy"
            icon="arrow-trending-up"
            height="min-h-[200px]"
            title="Top produkty i trendy"
            class="mt-6"
        >
            <x-slot:placeholder>
                <div class="grid gap-4 lg:grid-cols-2">
                    @for($i = 0; $i < 2; $i++)
                        <div class="space-y-2">
                            @for($j = 0; $j < 5; $j++)
                                <div class="h-8 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                            @endfor
                        </div>
                    @endfor
                </div>
            </x-slot:placeholder>
        </x-dashboard-lazy-module>
    @endif
</div>