@props([
    'shopName',
    'shopColor'       => '#000000',
    'rank'            => null,
    'revenue'         => 0,
    'maxTransactions' => 0,
    'transactions'    => 0,
    'devices'         => 0,
    'accessories'     => 0,
    'services'        => 0,
])

{{-- Single shop card with stacked category bar --}}
@php
    $total       = $devices + $accessories + $services;
    $pctDevices  = $total > 0 ? round(($devices / $total) * 100, 1) : 0;
    $pctAccess   = $total > 0 ? round(($accessories / $total) * 100, 1) : 0;
    $pctServices = $total > 0 ? round(($services / $total) * 100, 1) : 0;
    // Fix rounding to sum to 100
    $pctServices = $total > 0 ? 100 - $pctDevices - $pctAccess : 0;
@endphp

<div class="rounded-lg border border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">

    {{-- Header: rank · shop name · totals --}}
    <div class="flex items-center gap-2.5">
        @if($rank)
            <span @class([
                'shrink-0 text-sm font-bold tabular-nums w-5 text-center',
                'text-amber-500 dark:text-amber-400' => $rank === 1,
                'text-zinc-300 dark:text-zinc-600'    => $rank !== 1,
            ])>{{ $rank }}</span>
        @endif

        <span class="size-2.5 shrink-0 rounded-full" style="background:{{ $shopColor }}"></span>
        <span class="min-w-0 flex-1 truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $shopName }}</span>

        <span class="shrink-0 text-sm font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
            {{ $total }} sprzedaży
        </span>
        <span class="shrink-0 w-20 text-right text-xs tabular-nums text-zinc-400 dark:text-zinc-500">
            {{ number_format($revenue / 100, 0, ',', ' ') }} zł
        </span>
    </div>

    @if($total > 0)
        {{-- Stacked proportion bar --}}
        <div class="mt-2 {{ $rank ? 'ml-[30px]' : '' }} flex h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
            @if($pctDevices > 0)
                <div
                    class="h-full bg-blue-400 dark:bg-blue-500 transition-all duration-500"
                    style="width:{{ $pctDevices }}%"
                    title="Urządzenia: {{ $devices }}"
                ></div>
            @endif
            @if($pctAccess > 0)
                <div
                    class="h-full bg-emerald-400 dark:bg-emerald-500 transition-all duration-500"
                    style="width:{{ $pctAccess }}%"
                    title="Akcesoria: {{ $accessories }}"
                ></div>
            @endif
            @if($pctServices > 0)
                <div
                    class="h-full bg-amber-400 dark:bg-amber-500 transition-all duration-500"
                    style="width:{{ $pctServices }}%"
                    title="Usługi: {{ $services }}"
                ></div>
            @endif
        </div>

        {{-- Inline legend with labels and counts --}}
        <div class="mt-1.5 {{ $rank ? 'ml-[30px]' : '' }} flex items-center gap-3 text-xs text-zinc-400 dark:text-zinc-500">
            <span class="flex items-center gap-1">
                <span class="size-1.5 rounded-full bg-blue-400 dark:bg-blue-500"></span>
                Urz. {{ $devices }}
            </span>
            <span class="flex items-center gap-1">
                <span class="size-1.5 rounded-full bg-emerald-400 dark:bg-emerald-500"></span>
                Akc. {{ $accessories }}
            </span>
            <span class="flex items-center gap-1">
                <span class="size-1.5 rounded-full bg-amber-400 dark:bg-amber-500"></span>
                Usł. {{ $services }}
            </span>
        </div>
    @else
        <div class="mt-2 {{ $rank ? 'ml-[30px]' : '' }} text-xs text-zinc-300 dark:text-zinc-600">
            Brak sprzedaży
        </div>
    @endif
</div>