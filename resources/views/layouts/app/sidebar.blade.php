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
                @endphp

                <flux:sidebar.item icon="home" :href="route($routePrefix . 'dashboard', $shopParam)" :current="request()->routeIs('*dashboard')" wire:navigate>
                    Dashboard
                </flux:sidebar.item>
                <flux:sidebar.item icon="archive-box" :href="route($routePrefix . 'items.index', $shopParam)" :current="request()->routeIs('*items.*')" wire:navigate>
                    Magazyn
                </flux:sidebar.item>
                <flux:sidebar.item icon="shopping-cart" :href="route($routePrefix . 'sells.index', $shopParam)" :current="request()->routeIs('*sells.*')" wire:navigate>
                    Sprzedaż
                </flux:sidebar.item>
                <flux:sidebar.item icon="truck" :href="route($routePrefix . 'purchases.index', $shopParam)" :current="request()->routeIs('*purchases.*')" wire:navigate>
                    Zakupy
                </flux:sidebar.item>
                <flux:sidebar.item icon="arrows-right-left" :href="route($routePrefix . 'dashboard', $shopParam)" :current="request()->routeIs('*transfers.*')" wire:navigate>
                    Transfery
                </flux:sidebar.item>
                {{-- Admin-only: user management --}}
                @if(auth()->user()?->isAdmin())
                    <flux:sidebar.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>
                        Użytkownicy
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

        @fluxScripts
    </body>
</html>