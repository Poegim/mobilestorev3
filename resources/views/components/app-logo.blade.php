@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="{{ env('APP_NAME') }}" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <img src="{{ asset('favicon.png') }}" alt="MobileStore">
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="{{ env('APP_NAME') }}" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <img src="{{ asset('favicon.png') }}" alt="MobileStore">
        </x-slot>
    </flux:brand>
@endif
