@props([
    'shopName'                   => '',
    'shopColor'                  => '#000000',
    'shopId'                     => null,
    'rank'                       => null,
    'revenue'                    => 0,
    'maxTransactions'            => 0,
    'transactions'               => 0,
    'devices'                    => 0,
    'accessories'                => 0,
    'services'                   => 0,
    'accessoryMarginPctCurrent'  => null,
    'accessoryMarginPctPrevious' => null,
])

@php
    $total       = $devices + $accessories + $services;
    $pctDevices  = $total > 0 ? round(($devices / $total) * 100, 1) : 0;
    $pctAccess   = $total > 0 ? round(($accessories / $total) * 100, 1) : 0;
    $pctServices = $total > 0 ? 100 - $pctDevices - $pctAccess : 0;
@endphp

<div
    class="rounded-lg border border-zinc-200 dark:border-zinc-700 px-4 py-3 border-l-[4px] flex gap-4"
    style="border-left-color: {{ $shopColor }}; background: color-mix(in oklch, {{ $shopColor }} 4%, transparent);"
>
    {{-- Column 1: avatar ONLY --}}
    <div class="shrink-0 flex items-center">
        <flux:avatar name="{{ $shopName }}" size="xl" />
    </div>

    {{-- Column 2: ALL text --}}
    <div class="flex-1 min-w-0 flex flex-col justify-between gap-2">

        {{-- Row: name + revenue --}}
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100 hover:underline">
                    <flux:link href="{{ route('dashboard', ['shop' => $shopId]) }}" wire:navigate>
                        {{ $shopName }}
                    </flux:link>
                </span>
                <span class="shrink-0 text-xs font-medium text-zinc-400 dark:text-zinc-500">
                    #{{ $shopId }}
                </span>
            </div>
            <span class="shrink-0 text-xs tabular-nums text-zinc-400 dark:text-zinc-500">
                {{ number_format($revenue / 100, 0, ',', ' ') }} zł
            </span>
        </div>

        {{-- Transaction count --}}
        <div class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
            {{ $total }} sprzedaży
        </div>

        @if($total > 0)
            {{-- Stacked proportion bar --}}
            <div class="flex h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                @if($pctDevices > 0)
                    <div class="h-full bg-blue-400 dark:bg-blue-500 transition-all duration-500"
                        style="width:{{ $pctDevices }}%" title="Urządzenia: {{ $devices }}"></div>
                @endif
                @if($pctAccess > 0)
                    <div class="h-full bg-emerald-400 dark:bg-emerald-500 transition-all duration-500"
                        style="width:{{ $pctAccess }}%" title="Akcesoria: {{ $accessories }}"></div>
                @endif
                @if($pctServices > 0)
                    <div class="h-full bg-amber-400 dark:bg-amber-500 transition-all duration-500"
                        style="width:{{ $pctServices }}%" title="Usługi: {{ $services }}"></div>
                @endif
            </div>

            {{-- Legend --}}
            <div class="flex items-center gap-3 text-xs text-zinc-400 dark:text-zinc-500">
                <span class="flex items-center gap-1">
                    <span class="size-1.5 rounded-full bg-blue-400 dark:bg-blue-500"></span>
                    Urz. {{ $devices }}
                    <span class="text-zinc-300 dark:text-zinc-600">({{ round($pctDevices) }}%)</span>
                </span>
                <span class="flex items-center gap-1">
                    <span class="size-1.5 rounded-full bg-emerald-400 dark:bg-emerald-500"></span>
                    Akc. {{ $accessories }}
                    <span class="text-zinc-300 dark:text-zinc-600">({{ round($pctAccess) }}%)</span>
                </span>
                <span class="flex items-center gap-1">
                    <span class="size-1.5 rounded-full bg-amber-400 dark:bg-amber-500"></span>
                    Usł. {{ $services }}
                    <span class="text-zinc-300 dark:text-zinc-600">({{ round($pctServices) }}%)</span>
                </span>
            </div>
        @else
            <div class="text-xs text-zinc-300 dark:text-zinc-600">Brak sprzedaży</div>
        @endif

        {{-- Accessory margin — always current vs previous month, independent of period --}}
        @if($accessoryMarginPctCurrent !== null)
            <div class="flex items-center justify-between text-xs pt-1.5 border-t border-zinc-100 dark:border-zinc-800">
                <span class="text-zinc-400 dark:text-zinc-500">Marża akc.</span>
                <div class="flex items-center gap-1.5 tabular-nums">
                    @if($accessoryMarginPctPrevious !== null)
                        <span class="text-zinc-400 dark:text-zinc-500">{{ $accessoryMarginPctPrevious }}%</span>
                        <span class="text-zinc-300 dark:text-zinc-600">→</span>
                    @endif
                    <span @class([
                        'font-medium',
                        'text-emerald-500' => $accessoryMarginPctCurrent >= ($accessoryMarginPctPrevious ?? $accessoryMarginPctCurrent),
                        'text-red-400'     => $accessoryMarginPctCurrent <  ($accessoryMarginPctPrevious ?? $accessoryMarginPctCurrent),
                    ])>{{ $accessoryMarginPctCurrent }}%</span>
                </div>
            </div>
        @endif

    </div>
</div>