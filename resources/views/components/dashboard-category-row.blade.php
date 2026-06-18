@props([
    'icon',
    'label',
    'revenue',
])

{{-- Compact category row for month summary panel --}}
<div class="flex items-center justify-between text-sm">
    <span class="flex items-center gap-1.5 text-zinc-500 dark:text-zinc-400">
        <flux:icon :name="$icon" variant="mini" class="size-3.5" />
        {{ $label }}
    </span>
    <span class="font-medium text-zinc-900 dark:text-zinc-100">
        {{ number_format($revenue / 100, 2, ',', ' ') }} zł
    </span>
</div>