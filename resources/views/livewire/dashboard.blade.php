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

    @if($isAdmin)
        {{-- ===== ADMIN: revenue scorecard + month panel ===== --}}
        <div class="grid gap-4 lg:grid-cols-[1fr_280px] mb-6">

            {{-- LEFT — Today's revenue scorecard --}}
            <flux:card>
                <flux:text class="text-xs uppercase tracking-wide">Obrót dziś</flux:text>
                <div class="mt-1 text-4xl font-medium text-zinc-900 dark:text-zinc-50 leading-none">
                    {{ number_format($todayRevenue / 100, 2, ',', ' ') }} zł
                </div>
                <flux:text class="mt-1 text-sm">
                    {{ $todaySells }} {{ $todaySells === 1 ? 'transakcja' : 'transakcji' }}
                </flux:text>

                {{-- Category breakdown bars --}}
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

                {{-- Total profit --}}
                @if(isset($todayCategories['totalProfit']))
                    <div class="mt-5 pt-4 border-t border-zinc-200 dark:border-zinc-700 flex items-baseline justify-between">
                        <flux:text class="text-sm">Zysk łączny dziś</flux:text>
                        <span class="text-lg font-medium {{ $todayCategories['totalProfit'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $todayCategories['totalProfit'] >= 0 ? '+' : '' }}{{ number_format($todayCategories['totalProfit'] / 100, 2, ',', ' ') }} zł
                        </span>
                    </div>
                @endif
            </flux:card>

            {{-- RIGHT — Month summary + compact stats --}}
            <div class="flex flex-col gap-4">

                {{-- Month summary --}}
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

                {{-- Stock count --}}
                <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/60 px-4 py-3">
                    <flux:text class="text-sm">Na magazynie</flux:text>
                    <div class="text-2xl font-medium text-zinc-900 dark:text-zinc-50 leading-none mt-1">
                        {{ number_format($itemsInStock, 0, ',', ' ') }}
                    </div>
                    <flux:text class="text-xs mt-0.5">przedmiotów</flux:text>
                </div>

                {{-- Today purchases --}}
                <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/60 px-4 py-3">
                    <flux:text class="text-sm">Zakupy dziś</flux:text>
                    <div class="text-2xl font-medium text-zinc-900 dark:text-zinc-50 leading-none mt-1">
                        {{ $todayPurchases }}
                    </div>
                    <flux:text class="text-xs mt-0.5">przyjęć towaru</flux:text>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== SHOP STATS — visible to everyone ===== --}}
    <div class="{{ $isAdmin ? '' : '' }}">
        @if($isAdmin && !$shop)
            <flux:heading size="lg" class="mb-3">Sklepy — ilości</flux:heading>
        @endif

        <div class="grid gap-3 {{ $shop ? '' : 'sm:grid-cols-2' }}">
            @foreach($shopStats as $stat)
                <x-dashboard-shop-card
                    :shop-name="$stat['shop']->name"
                    :shop-color="$stat['shop']->color"
                    :stock="$stat['stock']"
                    :today="$stat['today']"
                    :month="$stat['month']"
                />
            @endforeach
        </div>
    </div>
    <div class="mt-6">  
        <livewire:top-products :shop="$shop" />
    </div>
</div>