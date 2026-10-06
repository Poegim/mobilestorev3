@props([
    'icon',
    'href',
    'current' => false,
    'badge' => null,
    // When null (e.g. no shop selected) the quick "new" action is not rendered at all
    'newHref' => null,
    'newLabel' => 'Nowy',
])

<div class="group/nav relative">
    <flux:sidebar.item :icon="$icon" :href="$href" :current="$current" :badge="$badge" badge:color="amber" wire:navigate>
        {{ $slot }}
    </flux:sidebar.item>

    @if($newHref)
        {{-- Quick "new" action at the right edge of the row (shifted left when a badge is shown).
             Slides/fades in on hover, stays visible while the section is active (touch devices have no hover).
             Hidden when the sidebar is collapsed to icons: there is no room for it. --}}
        <div @class([
            'pointer-events-none absolute inset-y-0 flex items-center in-data-flux-sidebar-collapsed-desktop:hidden',
            'end-2' => ! $badge,
            'end-10' => $badge,
        ])>
            <a
                href="{{ $newHref }}"
                wire:navigate
                title="{{ $newLabel }}"
                aria-label="{{ $newLabel }}"
                @class([
                    'inline-flex size-6 items-center justify-center rounded-md',
                    'bg-emerald-500/15 text-emerald-500 transition duration-200 ease-out',
                    'hover:bg-emerald-500 hover:text-white focus-visible:bg-emerald-500 focus-visible:text-white',
                    // Active section: always shown
                    'pointer-events-auto opacity-100 scale-100 translate-x-0' => $current,
                    // Otherwise: hidden until the row is hovered or the link is focused
                    'opacity-0 scale-75 translate-x-1 focus-visible:pointer-events-auto focus-visible:opacity-100 focus-visible:scale-100 focus-visible:translate-x-0 group-hover/nav:pointer-events-auto group-hover/nav:opacity-100 group-hover/nav:scale-100 group-hover/nav:translate-x-0' => ! $current,
                ])
            >
                <flux:icon.plus variant="micro" class="size-4" />
            </a>
        </div>
    @endif
</div>