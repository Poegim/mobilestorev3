<div>

    {{-- Shop-scoped rankings --}}
    @if($shop)
        <flux:subheading class="mb-2">{{ $shop->name }}</flux:subheading>

        <div class="grid gap-4 lg:grid-cols-2 mb-6">
            <flux:card class="!p-0 overflow-hidden">
                <div class="flex items-center justify-between px-4 pt-3 pb-2">
                    <flux:heading>Top produkty</flux:heading>
                    <span class="text-xs text-zinc-400">12 msc</span>
                </div>
                <div>
                    @forelse($this->shopTop as $i => $product)
                        <div @class([
                            'grid grid-cols-[22px_minmax(0,1fr)_auto_auto] items-center gap-2 px-4 py-2',
                            'bg-zinc-50 dark:bg-zinc-800/40' => $i % 2 === 0,
                        ])>
                            <span @class([
                                'text-xs font-bold tabular-nums text-center',
                                'text-amber-500 dark:text-amber-400' => $i < 3,
                                'text-zinc-300 dark:text-zinc-600'    => $i >= 3,
                            ])>{{ $i + 1 }}</span>

                            <div class="min-w-0">
                                <span class="block text-[11px] text-zinc-400 dark:text-zinc-500 leading-tight">{{ $product['brand'] }}</span>
                                <span class="block truncate text-sm font-medium text-zinc-900 dark:text-zinc-100 leading-tight" title="{{ $product['brand'] }} {{ $product['name'] }}">{{ $product['name'] }}</span>
                            </div>

                            @if($product['in_stock'] !== null)
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-[11px] font-medium tabular-nums whitespace-nowrap',
                                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' => $product['in_stock'] > 0,
                                    'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500'               => $product['in_stock'] === 0,
                                ])>{{ $product['in_stock'] }} szt.</span>
                            @endif

                            <span class="text-sm font-semibold tabular-nums text-zinc-900 dark:text-zinc-100 text-right min-w-[50px]">
                                {{ $product['count'] }}
                                <span class="text-[11px] font-normal text-zinc-400">sp.</span>
                            </span>
                        </div>
                    @empty
                        <div class="px-4 py-6 text-center text-sm text-zinc-400">Brak danych</div>
                    @endforelse
                </div>
            </flux:card>

            <flux:card class="!p-0 overflow-hidden">
                <div class="flex items-center gap-2 px-4 pt-3 pb-2">
                    <flux:heading>Trending</flux:heading>
                    <flux:icon name="arrow-trending-up" class="size-4 text-emerald-500" />
                    <span class="ml-auto text-xs text-zinc-400">3 msc</span>
                </div>
                <div>
                    @forelse($this->shopTrending as $i => $product)
                        <div @class([
                            'grid grid-cols-[22px_minmax(0,1fr)_auto_auto] items-center gap-2 px-4 py-2',
                            'bg-zinc-50 dark:bg-zinc-800/40' => $i % 2 === 0,
                        ])>
                            <span class="text-xs font-bold tabular-nums text-center text-emerald-500 dark:text-emerald-400">{{ $i + 1 }}</span>

                            <div class="min-w-0">
                                <span class="block text-[11px] text-zinc-400 dark:text-zinc-500 leading-tight">{{ $product['brand'] }}</span>
                                <span class="block truncate text-sm font-medium text-zinc-900 dark:text-zinc-100 leading-tight" title="{{ $product['brand'] }} {{ $product['name'] }}">{{ $product['name'] }}</span>
                            </div>

                            @if($product['in_stock'] !== null)
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-[11px] font-medium tabular-nums whitespace-nowrap',
                                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' => $product['in_stock'] > 0,
                                    'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500'               => $product['in_stock'] === 0,
                                ])>{{ $product['in_stock'] }} szt.</span>
                            @endif

                            <span class="text-sm font-semibold tabular-nums text-zinc-900 dark:text-zinc-100 text-right min-w-[50px]">
                                {{ $product['count'] }}
                                <span class="text-[11px] font-normal text-zinc-400">sp.</span>
                            </span>
                        </div>
                    @empty
                        <div class="px-4 py-6 text-center text-sm text-zinc-400">Brak nowych trendów</div>
                    @endforelse
                </div>
            </flux:card>
        </div>
    @endif

    {{-- Global rankings --}}
    <flux:subheading class="mb-2">Cała sieć</flux:subheading>

    <div class="grid gap-4 lg:grid-cols-2">
        <flux:card class="!p-0 overflow-hidden">
            <div class="flex items-center justify-between px-4 pt-3 pb-2">
                <flux:heading>Top produkty</flux:heading>
                <span class="text-xs text-zinc-400">12 msc</span>
            </div>
            <div>
                @forelse($this->globalTop as $i => $product)
                    <div @class([
                        'grid grid-cols-[22px_minmax(0,1fr)_auto_auto] items-center gap-2 px-4 py-2',
                        'bg-zinc-50 dark:bg-zinc-800/40' => $i % 2 === 0,
                    ])>
                        <span @class([
                            'text-xs font-bold tabular-nums text-center',
                            'text-amber-500 dark:text-amber-400' => $i < 3,
                            'text-zinc-300 dark:text-zinc-600'    => $i >= 3,
                        ])>{{ $i + 1 }}</span>

                        <div class="min-w-0">
                            <span class="block text-[11px] text-zinc-400 dark:text-zinc-500 leading-tight">{{ $product['brand'] }}</span>
                            <span class="block truncate text-sm font-medium text-zinc-900 dark:text-zinc-100 leading-tight" title="{{ $product['brand'] }} {{ $product['name'] }}">{{ $product['name'] }}</span>
                        </div>

                        @if($product['in_stock'] !== null)
                            <span @class([
                                'rounded-full px-2 py-0.5 text-[11px] font-medium tabular-nums whitespace-nowrap',
                                'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' => $product['in_stock'] > 0,
                                'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500'               => $product['in_stock'] === 0,
                            ])>{{ $product['in_stock'] }} szt.</span>
                        @endif

                        <span class="text-sm font-semibold tabular-nums text-zinc-900 dark:text-zinc-100 text-right min-w-[50px]">
                            {{ $product['count'] }}
                            <span class="text-[11px] font-normal text-zinc-400">sp.</span>
                        </span>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-sm text-zinc-400">Brak danych</div>
                @endforelse
            </div>
        </flux:card>

        <flux:card class="!p-0 overflow-hidden">
            <div class="flex items-center gap-2 px-4 pt-3 pb-2">
                <flux:heading>Trending</flux:heading>
                <flux:icon name="arrow-trending-up" class="size-4 text-emerald-500" />
                <span class="ml-auto text-xs text-zinc-400">3 msc</span>
            </div>
            <div>
                @forelse($this->globalTrending as $i => $product)
                    <div @class([
                        'grid grid-cols-[22px_minmax(0,1fr)_auto_auto] items-center gap-2 px-4 py-2',
                        'bg-zinc-50 dark:bg-zinc-800/40' => $i % 2 === 0,
                    ])>
                        <span class="text-xs font-bold tabular-nums text-center text-emerald-500 dark:text-emerald-400">{{ $i + 1 }}</span>

                        <div class="min-w-0">
                            <span class="block text-[11px] text-zinc-400 dark:text-zinc-500 leading-tight">{{ $product['brand'] }}</span>
                            <span class="block truncate text-sm font-medium text-zinc-900 dark:text-zinc-100 leading-tight" title="{{ $product['brand'] }} {{ $product['name'] }}">{{ $product['name'] }}</span>
                        </div>

                        @if($product['in_stock'] !== null)
                            <span @class([
                                'rounded-full px-2 py-0.5 text-[11px] font-medium tabular-nums whitespace-nowrap',
                                'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' => $product['in_stock'] > 0,
                                'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500'               => $product['in_stock'] === 0,
                            ])>{{ $product['in_stock'] }} szt.</span>
                        @endif

                        <span class="text-sm font-semibold tabular-nums text-zinc-900 dark:text-zinc-100 text-right min-w-[50px]">
                            {{ $product['count'] }}
                            <span class="text-[11px] font-normal text-zinc-400">sp.</span>
                        </span>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-sm text-zinc-400">Brak nowych trendów</div>
                @endforelse
            </div>
        </flux:card>
    </div>

</div>