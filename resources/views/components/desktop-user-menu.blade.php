<flux:dropdown position="bottom" align="start">
    <flux:sidebar.profile
        :name="auth()->user()->name"
        :initials="auth()->user()->initials()"
        icon:trailing="chevrons-up-down"
        data-test="sidebar-menu-button"
    />

    <flux:menu>
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
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
