@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="{{ env('APP_NAME') }}" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-6 items-center justify-center rounded-md">
            <img src="{{ asset('favicon.png') }}" alt="MobileStore" class="size-5">
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="{{ env('APP_NAME') }}" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-6 items-center justify-center rounded-md">
            <img src="{{ asset('favicon.png') }}" alt="MobileStore" class="size-5">
        </x-slot>
    </flux:brand>
@endif