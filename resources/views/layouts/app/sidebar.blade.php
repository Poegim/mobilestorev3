<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>


            <flux:sidebar.nav>
                <x-shop-switcher />
            </flux:sidebar.nav>

            <flux:separator />

            <flux:sidebar.nav>
                @php
                    $currentShop = request()->route('shop');
                    $shopParam = $currentShop ? ['shop' => $currentShop] : [];
                    $routePrefix = $currentShop ? 'shop.' : '';
                    $incomingTransfers = \App\Models\Transfer::awaitingReceiptFor(auth()->user(), $currentShop)->count();
                @endphp

                <flux:sidebar.item icon="home" :href="route($routePrefix . 'dashboard', $shopParam)" :current="request()->routeIs('*dashboard')" wire:navigate>
                    Dashboard
                </flux:sidebar.item>
                <flux:sidebar.item icon="archive-box" :href="route($routePrefix . 'items.index', $shopParam)" :current="request()->routeIs('*items.*')" wire:navigate>
                    Magazyn
                </flux:sidebar.item>
                <x-sidebar-item-with-new
                    icon="shopping-cart"
                    :href="route($routePrefix . 'sells.index', $shopParam)"
                    :current="request()->routeIs('*sells.*')"
                    :new-href="$currentShop ? '#' : null"
                >
                    Sprzedaż
                </x-sidebar-item-with-new>

                <x-sidebar-item-with-new
                    icon="truck"
                    :href="route($routePrefix . 'purchases.index', $shopParam)"
                    :current="request()->routeIs('*purchases.*')"
                    :new-href="$currentShop ? '#' : null"
                >
                    Zakupy
                </x-sidebar-item-with-new>

                <x-sidebar-item-with-new
                    icon="arrows-right-left"
                    :href="route($routePrefix . 'transfers.index', $shopParam)"
                    :current="request()->routeIs('*transfers.*')"
                    :badge="$incomingTransfers ?: null"
                    :new-href="$currentShop ? route('shop.transfers.create', $currentShop) : null"
                >
                    Transfery
                </x-sidebar-item-with-new>

                <flux:sidebar.item icon="user-group" :href="route('contacts.index')" :current="request()->routeIs('contacts.*')" wire:navigate>
                    Kontakty
                </flux:sidebar.item>

                {{-- Catalog: expandable group on the expanded sidebar --}}
                <flux:sidebar.group heading="Katalog" expandable :expanded="request()->routeIs('catalog.*')" class="in-data-flux-sidebar-collapsed-desktop:hidden">
                    <flux:sidebar.item icon="cube" :href="route('catalog.products.index')" :current="request()->routeIs('catalog.products.*')" wire:navigate>
                        Produkty
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="device-phone-mobile" :href="route('catalog.devices.index')" :current="request()->routeIs('catalog.devices.*')" wire:navigate>
                        Urządzenia
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="tag" :href="route('catalog.brands.index')" :current="request()->routeIs('catalog.brands.*')" wire:navigate>
                        Marki
                    </flux:sidebar.item>
                </flux:sidebar.group>

                {{-- Catalog: single icon with a flyout menu on the collapsed desktop sidebar --}}
                <flux:dropdown position="right" align="start" class="hidden in-data-flux-sidebar-collapsed-desktop:block">
                    <flux:sidebar.item icon="book-open" :current="request()->routeIs('catalog.*')" tooltip="Katalog" />

                    <flux:menu>
                        <flux:menu.item icon="cube" :href="route('catalog.products.index')" wire:navigate>Produkty</flux:menu.item>
                        <flux:menu.item icon="device-phone-mobile" :href="route('catalog.devices.index')" wire:navigate>Urządzenia</flux:menu.item>
                        <flux:menu.item icon="tag" :href="route('catalog.brands.index')" wire:navigate>Marki</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>

                <flux:separator />

                {{-- Admin-only: user management --}}
                @if(auth()->user()?->isAdmin())
                    <flux:sidebar.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>
                        Użytkownicy
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="building-storefront" :href="route('shops.index')" :current="request()->routeIs('shops.*')" wire:navigate>
                        Sklepy
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->login ?? auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <flux:spacer />
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        {{-- Show a toast flashed by the previous request (e.g. after a redirect) --}}
        @if(session('toast'))
            <div x-data x-init="Flux.toast(@js(session('toast')))"></div>
        @endif

        @fluxScripts
    </body>
</html>