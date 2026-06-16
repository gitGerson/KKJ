<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div class="space-y-1">
                <flux:heading size="xl" level="1">{{ __('Dashboard') }}</flux:heading>
                <flux:text>{{ __('Overview of umat, keluarga, area, and kemah data.') }}</flux:text>
            </div>

            <!-- <div class="flex flex-wrap gap-2">
                <flux:button size="sm" variant="filled" :href="route('umat.index')" wire:navigate>
                    {{ __('Manage umat') }}
                </flux:button>

                <flux:button size="sm" variant="primary" :href="route('keluarga.index')" wire:navigate>
                    {{ __('Manage keluarga') }}
                </flux:button>
            </div> -->
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('umat.index') }}" wire:navigate class="rounded-xl border border-neutral-200 bg-white p-5 transition hover:bg-zinc-50 dark:border-neutral-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                <div class="flex items-center justify-between gap-3">
                    <flux:text>{{ __('Umat') }}</flux:text>
                    <flux:badge color="blue">{{ __('Total') }}</flux:badge>
                </div>
                <div class="mt-4 text-3xl font-semibold text-zinc-950 dark:text-zinc-50">{{ number_format($totals['umat']) }}</div>
            </a>

            <a href="{{ route('keluarga.index') }}" wire:navigate class="rounded-xl border border-neutral-200 bg-white p-5 transition hover:bg-zinc-50 dark:border-neutral-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                <div class="flex items-center justify-between gap-3">
                    <flux:text>{{ __('Keluarga') }}</flux:text>
                    <flux:badge color="lime">{{ __('Total') }}</flux:badge>
                </div>
                <div class="mt-4 text-3xl font-semibold text-zinc-950 dark:text-zinc-50">{{ number_format($totals['keluarga']) }}</div>
            </a>

            <a href="{{ route('areas.index') }}" wire:navigate class="rounded-xl border border-neutral-200 bg-white p-5 transition hover:bg-zinc-50 dark:border-neutral-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                <div class="flex items-center justify-between gap-3">
                    <flux:text>{{ __('Areas') }}</flux:text>
                    <flux:badge color="amber">{{ __('Total') }}</flux:badge>
                </div>
                <div class="mt-4 text-3xl font-semibold text-zinc-950 dark:text-zinc-50">{{ number_format($totals['area']) }}</div>
            </a>

            <a href="{{ route('kemah.index') }}" wire:navigate class="rounded-xl border border-neutral-200 bg-white p-5 transition hover:bg-zinc-50 dark:border-neutral-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                <div class="flex items-center justify-between gap-3">
                    <flux:text>{{ __('Kemah') }}</flux:text>
                    <flux:badge color="rose">{{ __('Total') }}</flux:badge>
                </div>
                <div class="mt-4 text-3xl font-semibold text-zinc-950 dark:text-zinc-50">{{ number_format($totals['kemah']) }}</div>
            </a>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-900">
                <div class="space-y-1">
                    <flux:heading>{{ __('Congregation growth') }}</flux:heading>
                    <flux:text>{{ __('Members joining and leaving this period.') }}</flux:text>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-4">
                    <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-950/40">
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Joined this month') }}</div>
                        <div class="mt-1 text-2xl font-semibold text-emerald-600 dark:text-emerald-400">+{{ number_format($growth['masuk_bulan']) }}</div>
                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('This year') }}: {{ number_format($growth['masuk_tahun']) }}</div>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-950/40">
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Left this month') }}</div>
                        <div class="mt-1 text-2xl font-semibold text-rose-600 dark:text-rose-400">-{{ number_format($growth['keluar_bulan']) }}</div>
                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('This year') }}: {{ number_format($growth['keluar_tahun']) }}</div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-900">
                <div class="space-y-1">
                    <flux:heading>{{ __('Age demographics') }}</flux:heading>
                    <flux:text>{{ __('Active congregation distribution by age group.') }}</flux:text>
                </div>

                <div class="mt-5 space-y-3">
                    @php($demografiTotal = array_sum($demografi))
                    @foreach ($demografi as $kelompok => $jumlah)
                        <div>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="font-medium text-zinc-950 dark:text-zinc-50">{{ __(ucfirst($kelompok)) }}</span>
                                <span class="text-zinc-600 dark:text-zinc-400">{{ number_format($jumlah) }}</span>
                            </div>
                            <div class="mt-1 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-full rounded-full bg-blue-500" style="width: {{ $demografiTotal > 0 ? round($jumlah / $demografiTotal * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-900">
                <div class="space-y-1">
                    <flux:heading>{{ __('Unassigned data') }}</flux:heading>
                    <flux:text>{{ __('Umat records missing relationship data.') }}</flux:text>
                </div>

                <div class="mt-5 space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Without area') }}</span>
                        <span class="font-medium text-zinc-950 dark:text-zinc-50">{{ number_format($unassigned['area']) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Without kemah') }}</span>
                        <span class="font-medium text-zinc-950 dark:text-zinc-50">{{ number_format($unassigned['kemah']) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Without keluarga') }}</span>
                        <span class="font-medium text-zinc-950 dark:text-zinc-50">{{ number_format($unassigned['keluarga']) }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-900">
                <div class="space-y-1">
                    <flux:heading>{{ __('Top areas') }}</flux:heading>
                    <flux:text>{{ __('Areas with the most umat.') }}</flux:text>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($topAreas as $area)
                        <div class="flex items-center justify-between gap-3">
                            <span class="truncate text-sm font-medium text-zinc-950 dark:text-zinc-50">{{ $area->name }}</span>
                            <flux:badge>{{ $area->umat_count }}</flux:badge>
                        </div>
                    @empty
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No area data yet.') }}</div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-900">
                <div class="space-y-1">
                    <flux:heading>{{ __('Top kemah') }}</flux:heading>
                    <flux:text>{{ __('Kemah with the most umat.') }}</flux:text>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($topKemah as $kemah)
                        <div class="flex items-center justify-between gap-3">
                            <span class="truncate text-sm font-medium text-zinc-950 dark:text-zinc-50">{{ $kemah->name }}</span>
                            <flux:badge>{{ $kemah->umat_count }}</flux:badge>
                        </div>
                    @empty
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No kemah data yet.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_24rem]">
            <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3 border-b border-neutral-200 p-5 dark:border-neutral-700">
                    <div class="space-y-1">
                        <flux:heading>{{ __('Latest umat') }}</flux:heading>
                        <flux:text>{{ __('Recently created umat records.') }}</flux:text>
                    </div>

                    <flux:button size="sm" variant="ghost" :href="route('umat.index')" wire:navigate>
                        {{ __('View all') }}
                    </flux:button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-700">
                        <thead class="bg-zinc-50 text-xs font-medium uppercase text-zinc-500 dark:bg-zinc-950/60 dark:text-zinc-400">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-start">{{ __('Name') }}</th>
                                <th scope="col" class="px-4 py-3 text-start">{{ __('Area') }}</th>
                                <th scope="col" class="px-4 py-3 text-start">{{ __('Kemah') }}</th>
                                <th scope="col" class="px-4 py-3 text-start">{{ __('Keluarga') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                            @forelse ($latestUmat as $umat)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-zinc-950 dark:text-zinc-50">{{ $umat->nama_lengkap ?: '-' }}</div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $umat->nomor_telepon ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umat->area?->name ?: '-' }}</td>
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umat->kemah?->name ?: '-' }}</td>
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umat->keluarga?->no_keluarga ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                        {{ __('No umat records yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-900">
                <div class="space-y-1">
                    <flux:heading>{{ __('Largest keluarga') }}</flux:heading>
                    <flux:text>{{ __('Family records with the most umat.') }}</flux:text>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($largestKeluarga as $keluarga)
                        <div class="flex items-center justify-between gap-3">
                            <span class="truncate text-sm font-medium text-zinc-950 dark:text-zinc-50">{{ $keluarga->no_keluarga }}</span>
                            <flux:badge>{{ $keluarga->umat_count }}</flux:badge>
                        </div>
                    @empty
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No keluarga data yet.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-900">
            <div class="space-y-1">
                <flux:heading>{{ __('Recent changes') }}</flux:heading>
                <flux:text>{{ __('Latest changes to congregation data.') }}</flux:text>
            </div>

            <div class="mt-5 divide-y divide-neutral-200 dark:divide-neutral-700">
                @forelse ($recentActivities as $activity)
                    @php($changes = \App\Support\UmatActivityPresenter::changes($activity))
                    <div class="flex items-start justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-zinc-950 dark:text-zinc-50">
                                {{ $activity->description }}
                                @if ($name = $activity->subject?->nama_lengkap ?? data_get($activity->attribute_changes?->toArray() ?? [], 'attributes.nama_lengkap'))
                                    &mdash; <span class="text-zinc-600 dark:text-zinc-400">{{ $name }}</span>
                                @endif
                            </div>

                            @if (count($changes) > 0)
                                <ul class="mt-1 space-y-0.5">
                                    @foreach ($changes as $change)
                                        <li class="text-xs text-zinc-600 dark:text-zinc-400">
                                            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $change['label'] }}:</span>
                                            <span class="text-rose-600 line-through dark:text-rose-400">{{ $change['from'] }}</span>
                                            <span class="text-zinc-400">&rarr;</span>
                                            <span class="text-emerald-600 dark:text-emerald-400">{{ $change['to'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $activity->causer?->name ?? __('System') }}
                            </div>
                        </div>
                        <div class="whitespace-nowrap text-xs text-zinc-400">{{ $activity->created_at->diffForHumans() }}</div>
                    </div>
                @empty
                    <div class="py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No changes recorded yet.') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts::app>
