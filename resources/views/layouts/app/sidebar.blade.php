<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-brand-blue-950">
        <flux:sidebar sticky collapsible="mobile" class="kkj-brand-sidebar border-e border-brand-blue-900 bg-linear-to-b from-brand-blue-950 via-brand-blue-950 to-brand-red-950 text-white">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
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

            <!-- <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav> -->

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="border-b border-brand-blue-900 bg-brand-blue-950 text-white lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <div class="px-2 py-1 text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">
                        {{ __('Language') }}
                    </div>
                    <flux:menu.radio.group>
                        <form method="POST" action="{{ route('preferences.locale') }}" class="w-full">
                            @csrf
                            <input type="hidden" name="locale" value="id">
                            <flux:menu.item
                                as="button"
                                type="submit"
                                icon="{{ app()->getLocale() === 'id' ? 'check' : 'language' }}"
                                class="w-full cursor-pointer"
                            >
                                Bahasa Indonesia
                            </flux:menu.item>
                        </form>

                        <form method="POST" action="{{ route('preferences.locale') }}" class="w-full">
                            @csrf
                            <input type="hidden" name="locale" value="en">
                            <flux:menu.item
                                as="button"
                                type="submit"
                                icon="{{ app()->getLocale() === 'en' ? 'check' : 'language' }}"
                                class="w-full cursor-pointer"
                            >
                                English
                            </flux:menu.item>
                        </form>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <div class="px-2 py-1 text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">
                        {{ __('Theme') }}
                    </div>
                    <flux:menu.radio.group x-data>
                        <flux:menu.item icon="sun" x-on:click="$flux.appearance = 'light'">
                            {{ __('Light') }}
                        </flux:menu.item>
                        <flux:menu.item icon="moon" x-on:click="$flux.appearance = 'dark'">
                            {{ __('Dark') }}
                        </flux:menu.item>
                        <flux:menu.item icon="computer-desktop" x-on:click="$flux.appearance = 'system'">
                            {{ __('System') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
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
