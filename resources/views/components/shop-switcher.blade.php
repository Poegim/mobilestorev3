<div>
    <flux:dropdown>
        <flux:button variant="subtle" class="w-full justify-between">
            <span class="flex items-center gap-2">
                @if($currentShop)
                    <span class="size-2 rounded-full" style="background-color: {{ $currentShop->color }}"></span>
                    {{ Str::limit($currentShop->name, 25) }}
                @else
                    Wszystkie sklepy
                @endif
            </span>
            <flux:icon name="chevron-down" variant="micro" />
        </flux:button>

        <flux:menu>
            <flux:menu.item href="{{ route('dashboard') }}" wire:navigate>
                Wszystkie sklepy
            </flux:menu.item>

            <flux:separator />

            @foreach($shops as $shop)
                <flux:menu.item
                    href="{{ route('shop.dashboard', $shop) }}"
                    wire:navigate
                >
                    <span class="flex items-center gap-2">
                        <span class="size-2 rounded-full" style="background-color: {{ $shop->color }}"></span>
                        {{ Str::limit($shop->name, 25) }}
                    </span>
                </flux:menu.item>
            @endforeach
        </flux:menu>
    </flux:dropdown>
</div>