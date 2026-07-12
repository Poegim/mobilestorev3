@props([
    'icon',
    'label',
    'revenue',
    'count',
    'profit'      => null,
    'percentage'  => 0,
    'totalProfit' => null,
    'color'       => 'bg-purple-500 dark:bg-purple-400',
])

@php
    $profitPct = ($totalProfit && $totalProfit > 0 && $profit !== null)
        ? round(($profit / $totalProfit) * 100)
        : null;
@endphp

<div class="space-y-1.5">
    {{-- Label + revenue --}}
    <div class="flex items-center justify-between">
        <span class="flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
            <flux:icon :name="$icon" variant="mini" class="size-4" />
            {{ $label }}
        </span>
        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100 tabular-nums">
            {{ number_format($revenue / 100, 2, ',', ' ') }} zł
            <span class="text-xs font-normal text-zinc-400 dark:text-zinc-500">({{ $percentage }}%)</span>
        </span>
    </div>

    {{-- Progress bar --}}
    <div class="h-1.5 rounded-full bg-zinc-100 dark:bg-zinc-700 overflow-hidden">
        <div class="h-full rounded-full {{ $color }}" style="width: {{ min($percentage, 100) }}%"></div>
    </div>

    {{-- Count + profit --}}
    @if($profit !== null)
        <div class="flex items-center justify-between text-xs">
            <span class="text-zinc-400 dark:text-zinc-500">{{ $count }} szt.</span>
            <span class="{{ $profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }} font-medium tabular-nums">
                {{ $profit >= 0 ? '+' : '' }}{{ number_format($profit / 100, 2, ',', ' ') }} zł
                @if($profitPct !== null)
                    <span class="font-normal opacity-60">({{ $profitPct }}%)</span>
                @endif
            </span>
        </div>
    @else
        <div class="text-xs text-zinc-400 dark:text-zinc-500">{{ $count }} szt.</div>
    @endif
</div>