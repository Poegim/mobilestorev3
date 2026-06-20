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

{{-- Single shop card — stack vertically to form a leaderboard --}}
@php
    $barPercent  = $maxTransactions > 0 ? round(($transactions / $maxTransactions) * 100) : 0;
    $catMax      = max($devices, $accessories, $services, 1);
@endphp

<div class="rounded-lg border border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">

    {{-- Main row: rank · shop · bar · totals --}}
    <div class="flex items-center gap-3">
        @if($rank)
            <span @class([
                'shrink-0 text-sm font-bold tabular-nums w-5 text-center',
                'text-amber-500 dark:text-amber-400' => $rank === 1,
                'text-zinc-300 dark:text-zinc-600'    => $rank !== 1,
            ])>{{ $rank }}</span>
        @endif

        <span class="size-2.5 shrink-0 rounded-full" style="background:{{ $shopColor }}"></span>
        <span class="w-32 shrink-0 truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $shopName }}</span>

        <div class="h-4 flex-1 overflow-hidden rounded bg-zinc-100 dark:bg-zinc-800">
            <div class="h-full rounded transition-all duration-500" style="width:{{ $barPercent }}%; background:{{ $shopColor }}"></div>
        </div>

        <span class="shrink-0 text-sm font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
            {{ $transactions }} tr.
        </span>

        <span class="shrink-0 w-20 text-right text-xs tabular-nums text-zinc-400 dark:text-zinc-500">
            {{ number_format($revenue / 100, 0, ',', ' ') }} zł
        </span>
    </div>

    {{-- Category breakdown bars --}}
    <div class="mt-2 ml-8 grid grid-cols-[auto_1fr_auto] items-center gap-x-2.5 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
        <span>Urządzenia</span>
        <div class="h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
            <div class="h-full rounded-full bg-blue-400 dark:bg-blue-500" style="width:{{ round(($devices / $catMax) * 100) }}%"></div>
        </div>
        <span class="tabular-nums text-right">{{ $devices }} szt.</span>

        <span>Akcesoria</span>
        <div class="h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
            <div class="h-full rounded-full bg-emerald-400 dark:bg-emerald-500" style="width:{{ round(($accessories / $catMax) * 100) }}%"></div>
        </div>
        <span class="tabular-nums text-right">{{ $accessories }} szt.</span>

        <span>Usługi</span>
        <div class="h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
            <div class="h-full rounded-full bg-amber-400 dark:bg-amber-500" style="width:{{ round(($services / $catMax) * 100) }}%"></div>
        </div>
        <span class="tabular-nums text-right">{{ $services }} szt.</span>
    </div>
</div>