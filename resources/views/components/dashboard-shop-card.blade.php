@props([
    'shopName'                   => '',
    'shopColor'                  => '#000000',
    'shopId'                     => null,
    'rank'                       => null,
    'revenue'                    => 0,
    'maxTransactions'            => 0,
    'transactions'               => 0,
    'devices'                    => 0,
    'devicesRevenue'             => 0,
    'devicesProfit'              => null,
    'accessories'                => 0,
    'accessoriesRevenue'         => 0,
    'accessoriesProfit'          => null,
    'services'                   => 0,
    'servicesRevenue'            => 0,
    'servicesProfit'             => null,
    'accessoryMarginPctCurrent'  => null,
    'accessoryMarginPctPrevious' => null,
])

@php
    $total       = $devices + $accessories + $services;

    // Revenue-based percentages — consistent with per-shop dashboard view.
    $catRevenue  = $devicesRevenue + $accessoriesRevenue + $servicesRevenue;
    $pctDevices  = $catRevenue > 0 ? round(($devicesRevenue / $catRevenue) * 100, 1) : 0;
    $pctAccess   = $catRevenue > 0 ? round(($accessoriesRevenue / $catRevenue) * 100, 1) : 0;
    $pctServices = $catRevenue > 0 ? 100 - $pctDevices - $pctAccess : 0;
    
    $categories = [
        [
            'label'   => 'Urządzenia',
            'abbr'    => 'Urz.',
            'count'   => $devices,
            'revenue' => $devicesRevenue,
            'profit'  => $devicesProfit,
            'pct'     => $pctDevices,
            'color'   => 'bg-blue-400 dark:bg-blue-500',
            'dot'     => 'bg-blue-400 dark:bg-blue-500',
        ],
        [
            'label'   => 'Akcesoria',
            'abbr'    => 'Akc.',
            'count'   => $accessories,
            'revenue' => $accessoriesRevenue,
            'profit'  => $accessoriesProfit,
            'pct'     => $pctAccess,
            'color'   => 'bg-emerald-400 dark:bg-emerald-500',
            'dot'     => 'bg-emerald-400 dark:bg-emerald-500',
        ],
        [
            'label'   => 'Usługi',
            'abbr'    => 'Usł.',
            'count'   => $services,
            'revenue' => $servicesRevenue,
            'profit'  => $servicesProfit,
            'pct'     => $pctServices,
            'color'   => 'bg-amber-400 dark:bg-amber-500',
            'dot'     => 'bg-amber-400 dark:bg-amber-500',
        ],
    ];
    
    // Total profit for percentage calculation (admin only).
    // $totalProfit = collect($categories)->sum('profit');
    $totalProfit = $devicesProfit !== null
        ? ($devicesProfit + $accessoriesProfit + $servicesProfit)
        : null;
    // Accessory margin display state.
    $hasCurrent  = $accessoryMarginPctCurrent !== null;
    $hasPrevious = $accessoryMarginPctPrevious !== null;
    $improved    = $hasCurrent && $hasPrevious && $accessoryMarginPctCurrent >= $accessoryMarginPctPrevious;
@endphp

<div
    class="rounded-lg border border-zinc-200 dark:border-zinc-700 px-4 py-3 border-l-[4px] flex gap-4"
    style="border-left-color: {{ $shopColor }}; background: color-mix(in oklch, {{ $shopColor }} 4%, transparent);"
>
    {{-- Column 1: avatar --}}
    <div class="shrink-0 flex items-center">
        <flux:avatar name="{{ $shopName }}" size="xl" />
    </div>

    {{-- Column 2: all text --}}
    <div class="flex-1 min-w-0 flex flex-col justify-between gap-2">

        {{-- Row: name + revenue --}}
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100 hover:underline">
                    <flux:link href="{{ route('shop.dashboard', ['shop' => $shopId]) }}">
                        {{ $shopName }}
                    </flux:link>
                </span>
                <span class="shrink-0 text-xs font-medium text-zinc-400 dark:text-zinc-500">
                    #{{ $shopId }}
                </span>
            </div>
        </div>
        @if($totalProfit !== null)
            <div class="shrink-0 flex items-center gap-1.5 text-xs tabular-nums">
                <span class="text-zinc-400 dark:text-zinc-500">
                    {{ number_format($revenue / 100, 0, ',', ' ') }} zł
                </span>
                <span class="text-zinc-300 dark:text-zinc-700">·</span>
                <span class="{{ $totalProfit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $totalProfit >= 0 ? '+' : '' }}{{ number_format($totalProfit / 100, 0, ',', ' ') }} zł
                </span>
            </div>
        @endif

        {{-- Sales count --}}
        <div class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
            {{ $transactions }} {{ $transactions === 1 ? 'sprzedaż' : 'sprzedaży' }}
            <span class="text-zinc-300 dark:text-zinc-600">·</span>
            {{ $total }} szt.
            <span class="text-zinc-300 dark:text-zinc-600">·</span>
            {{ number_format($transactions > 0 ? $total / $transactions : 0, 2) }} szt./sprzedaż
        </div>

        @if($total > 0)
            {{-- Stacked proportion bar --}}
            <div class="flex h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                @foreach($categories as $cat)
                    @if($cat['pct'] > 0)
                        <div class="h-full {{ $cat['color'] }} transition-all duration-500"
                            style="width:{{ $cat['pct'] }}%" title="{{ $cat['label'] }}: {{ round($cat['pct']) }}%"></div>
                    @endif
                @endforeach
            </div>

            {{-- Category details: 3 rows — label+count left, revenue+pct+profit right --}}
            <div class="space-y-1">
                @foreach($categories as $cat)
                    <div class="flex items-center justify-between gap-2 text-xs">
                        {{-- Left: dot + label + count --}}
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="size-1.5 shrink-0 rounded-full {{ $cat['dot'] }}"></span>
                            <span class="text-zinc-500 dark:text-zinc-400">{{ $cat['abbr'] }}</span>
                            <span class="tabular-nums text-zinc-400 dark:text-zinc-500">{{ $cat['count'] }} szt.</span>
                        </div>
                        {{-- Right: revenue (pct) · profit (pct) --}}
                        <div class="flex items-center gap-1.5 shrink-0 tabular-nums">
                            @if($totalProfit !== null)
                                <span class="text-zinc-700 dark:text-zinc-200 font-medium">
                                    {{ number_format($cat['revenue'] / 100, 0, ',', ' ') }} zł
                                </span>
                                <span class="text-zinc-400 dark:text-zinc-500">({{ round($cat['pct']) }}%)</span>
                                @if($cat['profit'] !== null)
                                    <span class="text-zinc-300 dark:text-zinc-700">·</span>
                                    @php
                                        $profitPct = $totalProfit != 0 ? round(($cat['profit'] / $totalProfit) * 100) : 0;
                                    @endphp
                                    <span class="{{ $cat['profit'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $cat['profit'] >= 0 ? '+' : '' }}{{ number_format($cat['profit'] / 100, 0, ',', ' ') }} zł
                                    </span>
                                    <span class="text-zinc-400 dark:text-zinc-500">({{ $profitPct }}%)</span>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-xs text-zinc-300 dark:text-zinc-600">Brak sprzedaży</div>
        @endif

        {{-- Accessory margin — previous vs current CALENDAR month --}}
        <div class="flex items-center justify-between text-xs pt-1.5 border-t border-zinc-100 dark:border-zinc-800">
            <span
                class="text-zinc-400 dark:text-zinc-500"
                title="Marża na akcesoriach: poprzedni miesiąc kalendarzowy → bieżący miesiąc kalendarzowy. Nie zależy od wybranego okresu."
            >Marża akc. (poprz. → bież. mies.)</span>

            <span class="tabular-nums">
                @if($hasPrevious)
                    <span class="text-zinc-400 dark:text-zinc-500">{{ $accessoryMarginPctPrevious }}%</span>
                    <span class="text-zinc-300 dark:text-zinc-600">→</span>
                @endif

                @if($hasCurrent)
                    <span @class([
                        'font-medium',
                        'text-emerald-600 dark:text-emerald-400' => $improved,
                        'text-red-600 dark:text-red-400'         => !$improved,
                    ])>{{ $accessoryMarginPctCurrent }}%</span>
                @else
                    <span class="text-zinc-300 dark:text-zinc-600">—</span>
                @endif
            </span>
        </div>
    </div>
</div>