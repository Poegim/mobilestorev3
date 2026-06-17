<flux:dropdown position="bottom" align="start">
    <flux:sidebar.item style="cursor: pointer;">
        <x-slot:icon>
            @if($currentShop)
                <svg class="size-5 shrink-0" viewBox="0 0 20 20" fill="{{ $currentShop->color }}">
                    <circle cx="10" cy="10" r="8" />
                </svg>
            @else
                <flux:icon.building-storefront variant="outline" />
            @endif
        </x-slot:icon>
        {{ $currentShop ? Str::limit($currentShop->name, 20) : 'Wszystkie sklepy' }}
    </flux:sidebar.item>

    <flux:menu>
        <flux:menu.item href="{{ route('dashboard') }}" wire:navigate>
            Wszystkie sklepy
        </flux:menu.item>
        <flux:separator />
        @foreach($shops as $shop)
            <flux:menu.item href="{{ route('shop.dashboard', $shop) }}" wire:navigate>
                <span class="flex items-center gap-2">
                    <span class="size-2 rounded-full" style="background-color: {{ $shop->color }}"></span>
                    {{ $shop->name }}
                </span>
            </flux:menu.item>
        @endforeach
    </flux:menu>
</flux:dropdown>