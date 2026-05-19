<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="min-h-screen bg-zinc-50 text-zinc-950 dark:bg-zinc-950 dark:text-zinc-50">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
