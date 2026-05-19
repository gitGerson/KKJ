<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-brand-blue-50 antialiased dark:bg-linear-to-b dark:from-brand-blue-950 dark:via-zinc-950 dark:to-brand-red-950">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-linear-to-br from-brand-blue-50 via-white to-brand-gold-50 p-6 dark:from-brand-blue-950 dark:via-zinc-950 dark:to-brand-red-950 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-2">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="mb-1 flex h-16 w-16 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-16 fill-current text-black dark:text-white" />
                    </span>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
