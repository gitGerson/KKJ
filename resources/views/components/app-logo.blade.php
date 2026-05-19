@props([
    'sidebar' => false,
    'subtitle' => 'GPdI Mahanaim Tegal',
])

@if($sidebar)
    <a {{ $attributes->class('flex h-14 items-center gap-3 px-2 in-data-flux-sidebar-collapsed-desktop:w-14 in-data-flux-sidebar-collapsed-desktop:px-0 in-data-flux-sidebar-collapsed-desktop:justify-center in-data-flux-sidebar-collapsed-desktop:in-data-flux-sidebar-active:absolute in-data-flux-sidebar-collapsed-desktop:in-data-flux-sidebar-active:opacity-0') }} data-flux-sidebar-brand>
        <span class="flex aspect-square size-14 shrink-0 items-center justify-center overflow-hidden rounded-md">
            <x-app-logo-icon class="size-14" />
        </span>

        <span class="flex min-w-0 flex-col in-data-flux-sidebar-collapsed-desktop:hidden">
            <span class="truncate text-sm/5 font-medium text-brand-blue-700 in-data-flux-sidebar:text-white dark:text-white">{{ config('app.name', 'KKJ') }}</span>
            <span class="truncate text-xs/4 text-brand-red-600 in-data-flux-sidebar:text-brand-gold-100 dark:text-brand-gold-300">{{ $subtitle }}</span>
        </span>
    </a>
@else
    <a {{ $attributes->class('me-4 flex h-14 items-center gap-3') }} data-flux-brand>
        <span class="flex aspect-square size-14 shrink-0 items-center justify-center overflow-hidden rounded-md">
            <x-app-logo-icon class="size-14" />
        </span>

        <span class="flex min-w-0 flex-col">
            <span class="truncate text-sm/5 font-medium text-brand-blue-700 in-data-flux-header:text-white dark:text-white">{{ config('app.name', 'KKJ') }}</span>
            <span class="truncate text-xs/4 text-brand-red-600 in-data-flux-header:text-brand-gold-100 dark:text-brand-gold-300">{{ $subtitle }}</span>
        </span>
    </a>
@endif
