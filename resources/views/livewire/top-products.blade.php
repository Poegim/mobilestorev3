<div>

    @php
        $sections = [
            [
                'label'  => $shop?->name,
                'show'   => (bool) $shop,
                'tables' => [
                    ['title' => 'Top produkty', 'period' => '12 msc', 'icon' => null,               'data' => $this->shopTop,      'variant' => 'top',      'empty' => 'Brak danych'],
                    ['title' => 'Trending',     'period' => '3 msc',  'icon' => 'arrow-trending-up', 'data' => $this->shopTrending, 'variant' => 'trending', 'empty' => 'Brak nowych trendów'],
                ],
            ],
            [
                'label'  => 'Cała sieć',
                'show'   => true,
                'tables' => [
                    ['title' => 'Top produkty', 'period' => '12 msc', 'icon' => null,               'data' => $this->globalTop,      'variant' => 'top',      'empty' => 'Brak danych'],
                    ['title' => 'Trending',     'period' => '3 msc',  'icon' => 'arrow-trending-up', 'data' => $this->globalTrending, 'variant' => 'trending', 'empty' => 'Brak nowych trendów'],
                ],
            ],
        ];
    @endphp

    @foreach($sections as $section)
        @if($section['show'])
            <flux:subheading class="mb-2">{{ $section['label'] }}</flux:subheading>

            <div class="grid gap-4 lg:grid-cols-2 mb-6">
                @foreach($section['tables'] as $table)
                    <flux:card class="!p-0 overflow-hidden">
                        {{-- Card header --}}
                        <div class="flex items-center gap-2 px-4 pt-3 pb-2">
                            <flux:heading>{{ $table['title'] }}</flux:heading>
                            @if($table['icon'])
                                <flux:icon :name="$table['icon']" class="size-4 text-emerald-500" />
                            @endif
                            <span class="ml-auto text-xs text-zinc-400">{{ $table['period'] }}</span>
                        </div>

                        {{-- Column headers --}}
                        <div class="grid grid-cols-[22px_minmax(0,1fr)_auto_auto] items-center gap-2 px-4 py-1.5 border-b border-zinc-200 dark:border-zinc-700">
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 text-center">#</span>
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Produkt</span>
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 text-right">Magazyn</span>
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 text-right min-w-[50px]">Sprzedaż</span>
                        </div>

                        {{-- Rows --}}
                        <div>
                            @forelse($table['data'] as $i => $product)
                                <div @class([
                                    'grid grid-cols-[22px_minmax(0,1fr)_auto_auto] items-center gap-2 px-4 py-2',
                                    'bg-zinc-50 dark:bg-zinc-800/40' => $i % 2 === 0,
                                ])>
                                    {{-- Rank --}}
                                    <span @class([
                                        'text-xs font-bold tabular-nums text-center',
                                        match($table['variant']) {
                                            'trending' => 'text-emerald-500 dark:text-emerald-400',
                                            default    => $i < 3
                                                ? 'text-amber-500 dark:text-amber-400'
                                                : 'text-zinc-300 dark:text-zinc-600',
                                        },
                                    ])>{{ $i + 1 }}</span>

                                    {{-- Product --}}
                                    <div class="min-w-0">
                                        <span class="block text-[11px] text-zinc-400 dark:text-zinc-500 leading-tight">{{ $product['brand'] }}</span>
                                        <span class="block truncate text-sm font-medium text-zinc-900 dark:text-zinc-100 leading-tight" title="{{ $product['brand'] }} {{ $product['name'] }}">{{ $product['name'] }}</span>
                                    </div>

                                    {{-- Stock --}}
                                    @if($product['in_stock'] !== null)
                                        <span @class([
                                            'rounded-full px-2 py-0.5 text-[11px] font-medium tabular-nums whitespace-nowrap text-right',
                                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' => $product['in_stock'] > 0,
                                            'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500'               => $product['in_stock'] === 0,
                                        ])>{{ $product['in_stock'] }} szt.</span>
                                    @else
                                        <span class="text-[11px] text-zinc-300 dark:text-zinc-600 text-right">—</span>
                                    @endif

                                    {{-- Sold --}}
                                    <span class="text-sm font-semibold tabular-nums text-zinc-900 dark:text-zinc-100 text-right min-w-[50px]">
                                        {{ $product['count'] }}
                                    </span>
                                </div>
                            @empty
                                <div class="px-4 py-6 text-center text-sm text-zinc-400">{{ $table['empty'] }}</div>
                            @endforelse
                        </div>
                    </flux:card>
                @endforeach
            </div>
        @endif
    @endforeach

</div>