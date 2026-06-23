@props([
    'loaded'     => false,
    'action'     => null,
    'label'      => 'Wczytaj dane',
    'icon'       => 'arrow-path',
    'height'     => 'min-h-[180px]',
    'title'      => null,
    'subtitle'   => null,
])

{{-- Lazy-loadable dashboard section with blurred placeholder --}}
<div {{ $attributes }}>
    @if($title)
        <div class="flex items-center justify-between mb-3">
            <flux:heading size="lg">{{ $title }}</flux:heading>
            @if($subtitle)
                <flux:text class="text-xs">{{ $subtitle }}</flux:text>
            @endif
        </div>
    @endif

    @if($loaded)
        {{ $slot }}
    @else
        <div class="relative {{ $height }} rounded-xl overflow-hidden">
            {{-- Blurred placeholder content --}}
            <div class="absolute inset-0 select-none pointer-events-none" aria-hidden="true">
                <div class="p-4 filter blur-[6px] opacity-40">
                    {{ $placeholder ?? '' }}
                </div>
            </div>

            {{-- Overlay with load button --}}
            <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-[2px] rounded-xl border border-zinc-200/50 dark:border-zinc-700/50">
                <flux:button
                    wire:click="{{ $action }}"
                    variant="subtle"
                    size="sm"
                    wire:loading.attr="disabled"
                    wire:target="{{ $action }}"
                >
                    <span wire:loading.remove wire:target="{{ $action }}" class="flex items-center gap-1.5">
                        <flux:icon :name="$icon" class="size-4" />
                        {{ $label }}
                    </span>
                    <span wire:loading wire:target="{{ $action }}" class="flex items-center gap-1.5">
                        <flux:icon name="arrow-path" class="size-4 animate-spin" />
                        Ładowanie…
                    </span>
                </flux:button>
            </div>
        </div>
    @endif
</div>