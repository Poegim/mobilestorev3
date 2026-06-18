@props([
    'icon',
    'label',
    'revenue',
    'count',
    'profit' => null,
    'percentage' => 0,
    'color' => 'bg-purple-500 dark:bg-purple-400',
])

{{-- Proportional revenue bar for dashboard scorecard --}}
<div class="space-y-1">
    <div class="flex items-baseline justify-between">
        <span class="flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
            <flux:icon :name="$icon" variant="mini" class="size-4" />
            {{ $label }}
        </span>
        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
            {{ number_format($revenue / 100, 2, ',', ' ') }} zł
        </span>
    </div>

    {{-- Progress bar --}}
    <div class="h-1.5 rounded-full bg-zinc-100 dark:bg-zinc-700 overflow-hidden">
        <div
            class="h-full rounded-full {{ $color }}"
            style="width: {{ min($percentage, 100) }}%"
        ></div>
    </div>

    <div class="flex items-baseline justify-between text-xs text-zinc-400 dark:text-zinc-500">
        <span>{{ $count }} szt.</span>
        @if($profit !== null)
            <span class="{{ $profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }} font-medium">
                {{ $profit >= 0 ? '+' : '' }}{{ number_format($profit / 100, 2, ',', ' ') }} zł
            </span>
        @endif
    </div>
</div>