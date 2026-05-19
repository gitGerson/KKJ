<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-brand-blue-950">
        <flux:header container class="kkj-brand-header border-b-2 border-brand-gold-500 bg-brand-blue-950 text-white">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>

                <flux:navbar.item icon="map" :href="route('areas.index')" :current="request()->routeIs('areas.*')" wire:navigate>
                    {{ __('Areas') }}
                </flux:navbar.item>

                <flux:navbar.item icon="building-office-2" :href="route('kemah.index')" :current="request()->routeIs('kemah.*')" wire:navigate>
                    {{ __('Kemah') }}
                </flux:navbar.item>

                <flux:navbar.item icon="users" :href="route('keluarga.index')" :current="request()->routeIs('keluarga.*')" wire:navigate>
                    {{ __('Keluarga') }}
                </flux:navbar.item>

                <flux:navbar.item icon="user-group" :href="route('umat.index')" :current="request()->routeIs('umat.*')" wire:navigate>
                    {{ __('Umat') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <flux:tooltip :content="__('Search')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="magnifying-glass" href="#" :label="__('Search')" />
                </flux:tooltip>
                <flux:tooltip :content="__('Repository')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="folder-git-2"
                        href="https://github.com/laravel/livewire-starter-kit"
                        target="_blank"
                        :label="__('Repository')"
                    />
                </flux:tooltip>
                <flux:tooltip :content="__('Documentation')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="book-open-text"
                        href="https://laravel.com/docs/starter-kits#livewire"
                        target="_blank"
                        :label="__('Documentation')"
                    />
                </flux:tooltip>
            </flux:navbar>

            <x-desktop-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="kkj-brand-sidebar border-e border-brand-blue-900 bg-linear-to-b from-brand-blue-950 via-brand-blue-950 to-brand-red-950 text-white lg:hidden">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard')  }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="map" :href="route('areas.index')" :current="request()->routeIs('areas.*')" wire:navigate>
                        {{ __('Areas') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="building-office-2" :href="route('kemah.index')" :current="request()->routeIs('kemah.*')" wire:navigate>
                        {{ __('Kemah') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="users" :href="route('keluarga.index')" :current="request()->routeIs('keluarga.*')" wire:navigate>
                        {{ __('Keluarga') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="user-group" :href="route('umat.index')" :current="request()->routeIs('umat.*')" wire:navigate>
                        {{ __('Umat') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>
        </flux:sidebar>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
