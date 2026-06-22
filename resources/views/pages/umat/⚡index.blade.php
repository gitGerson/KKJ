<?php

use App\Exports\UmatExport;
use App\Models\Area;
use App\Models\Keluarga;
use App\Models\Kemah;
use App\Models\Umat;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Umat')] class extends Component {
    use WithPagination;

    public string $search = '';
    public bool $showFormModal = false;
    public ?int $editingUmatId = null;

    /** Tampilkan jemaat arsip (keluar/meninggal) alih-alih list utama (calon/aktif). */
    public bool $showArchived = false;

    /** Saring hanya calon yang sudah dipantau >= 6 bulan. */
    public bool $onlyCalonMatang = false;

    public ?int $filterAreaId = null;
    public ?int $filterBulanUlangTahun = null;
    public ?string $filterKelompokUsia = null;

    /**
     * @var array<string, mixed>
     */
    public array $form = [
        'nama_lengkap' => '',
        'nama_panggilan' => '',
        'nomor_telepon' => '',
        'jenis_kelamin' => '',
        'status_perkawinan' => '',
        'hub_kk' => '',
        'golongan_darah' => '',
        'tempat_lahir' => '',
        'tanggal_lahir' => '',
        'alamat' => '',
        'kemah_id' => '',
        'area_id' => '',
        'keluarga_id' => '',
        'pendidikan' => '',
        'pekerjaan' => '',
        'domisili' => '',
        'status' => Umat::STATUS_CALON,
        'tanggal_masuk' => '',
        'tanggal_keluar' => '',
        'keterangan' => '',
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedShowArchived(): void
    {
        $this->onlyCalonMatang = false;
        $this->resetPage();
    }

    public function updatedOnlyCalonMatang(): void
    {
        if ($this->onlyCalonMatang) {
            $this->showArchived = false;
        }

        $this->resetPage();
    }

    public function updatedFilterAreaId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterBulanUlangTahun(): void
    {
        $this->resetPage();
    }

    public function updatedFilterKelompokUsia(): void
    {
        $this->resetPage();
    }

    public function filterUlangTahunBulanIni(): void
    {
        $this->filterBulanUlangTahun = (int) now()->month;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->filterAreaId = null;
        $this->filterBulanUlangTahun = null;
        $this->filterKelompokUsia = null;
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $umatId): void
    {
        $umat = Umat::query()->findOrFail($umatId);

        $this->editingUmatId = $umat->id;
        $this->form = [
            'nama_lengkap' => $umat->nama_lengkap,
            'nama_panggilan' => $umat->nama_panggilan,
            'nomor_telepon' => $umat->nomor_telepon,
            'jenis_kelamin' => $umat->jenis_kelamin,
            'status_perkawinan' => $umat->status_perkawinan,
            'hub_kk' => $umat->hub_kk,
            'golongan_darah' => $umat->golongan_darah,
            'tempat_lahir' => $umat->tempat_lahir,
            'tanggal_lahir' => $umat->tanggal_lahir?->format('Y-m-d'),
            'alamat' => $umat->alamat,
            'kemah_id' => $umat->kemah_id,
            'area_id' => $umat->area_id,
            'keluarga_id' => $umat->keluarga_id,
            'pendidikan' => $umat->pendidikan,
            'pekerjaan' => $umat->pekerjaan,
            'domisili' => $umat->domisili,
            'status' => $umat->status,
            'tanggal_masuk' => $umat->tanggal_masuk?->format('Y-m-d'),
            'tanggal_keluar' => $umat->tanggal_keluar?->format('Y-m-d'),
            'keterangan' => $umat->keterangan,
        ];

        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function saveUmat(): void
    {
        Gate::authorize('manage-data');

        $this->normalizeForm();

        $payload = $this->validatedPayload();

        if ($this->editingUmatId) {
            Umat::query()->findOrFail($this->editingUmatId)->update($payload);
            Flux::toast(variant: 'success', text: __('Umat updated.'));
        } else {
            Umat::create($payload);
            Flux::toast(variant: 'success', text: __('Umat created.'));
        }

        $this->closeFormModal();
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function deleteUmat(int $umatId): void
    {
        Gate::authorize('manage-data');

        Umat::query()->findOrFail($umatId)->delete();

        Flux::toast(variant: 'success', text: __('Umat deleted.'));
    }

    public function export(): BinaryFileResponse
    {
        Gate::authorize('manage-data');

        return Excel::download(new UmatExport($this->filteredQuery()), 'umat-'.now()->format('Ymd-His').'.xlsx');
    }

    /**
     * Query Umat dengan seluruh filter aktif diterapkan (dipakai list & export).
     */
    public function filteredQuery(): Builder
    {
        return Umat::query()
            ->when($this->onlyCalonMatang, fn (Builder $query) => $query->calonMatang())
            ->when(! $this->onlyCalonMatang, function (Builder $query): void {
                $query->whereIn('status', $this->showArchived
                    ? Umat::STATUS_ARSIP
                    : Umat::STATUS_LIST_UTAMA);
            })
            ->when($this->filterAreaId, fn (Builder $query, int $areaId) => $query->area($areaId))
            ->when($this->filterBulanUlangTahun, fn (Builder $query, int $bulan) => $query->ulangTahunBulan($bulan))
            ->when($this->filterKelompokUsia, fn (Builder $query, string $kelompok) => $query->kelompokUsia($kelompok))
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query
                        ->where('nama_lengkap', 'like', '%'.$this->search.'%')
                        ->orWhere('nama_panggilan', 'like', '%'.$this->search.'%')
                        ->orWhere('nomor_telepon', 'like', '%'.$this->search.'%')
                        ->orWhere('domisili', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('nama_lengkap');
    }

    #[Computed]
    public function umat(): LengthAwarePaginator
    {
        return $this->filteredQuery()
            ->with(['area', 'kemah', 'keluarga'])
            ->paginate(10);
    }

    #[Computed]
    public function calonMatangCount(): int
    {
        return Umat::query()->calonMatang()->count();
    }

    public function promoteToAktif(int $umatId): void
    {
        Gate::authorize('manage-data');

        $umat = Umat::query()->findOrFail($umatId);
        $umat->update(['status' => Umat::STATUS_AKTIF]);

        unset($this->calonMatangCount);

        Flux::toast(variant: 'success', text: __('Umat promoted to active.'));
    }

    #[Computed]
    public function areas(): Collection
    {
        return Area::query()->orderBy('name')->get();
    }

    #[Computed]
    public function kemahOptions(): Collection
    {
        return Kemah::query()->orderBy('name')->get();
    }

    #[Computed]
    public function keluargaOptions(): Collection
    {
        return Keluarga::query()->orderBy('no_keluarga')->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(): array
    {
        $validated = $this->validate([
            'form.nama_lengkap' => ['required', 'string', 'max:255'],
            'form.nama_panggilan' => ['nullable', 'string', 'max:255'],
            'form.nomor_telepon' => ['nullable', 'string', 'max:255'],
            'form.jenis_kelamin' => ['required', 'string', Rule::in(Umat::jenisKelaminList())],
            'form.status_perkawinan' => ['required', 'string', Rule::in(Umat::statusPerkawinanList())],
            'form.hub_kk' => ['nullable', 'string', 'max:255'],
            'form.golongan_darah' => ['nullable', 'string', 'max:255'],
            'form.tempat_lahir' => ['nullable', 'string', 'max:255'],
            'form.tanggal_lahir' => ['required', 'date', 'before_or_equal:today'],
            'form.alamat' => ['nullable', 'string'],
            'form.kemah_id' => ['nullable', 'exists:kemah,id'],
            'form.area_id' => ['nullable', 'exists:area,id'],
            'form.keluarga_id' => ['nullable', 'exists:keluarga,id'],
            'form.pendidikan' => ['nullable', 'string', 'max:255'],
            'form.pekerjaan' => ['nullable', 'string', 'max:255'],
            'form.domisili' => ['nullable', 'string', 'max:255'],
            'form.status' => ['required', 'string', 'in:'.implode(',', Umat::statuses())],
            'form.tanggal_masuk' => ['nullable', 'date'],
            'form.tanggal_keluar' => ['nullable', 'date', 'after_or_equal:form.tanggal_masuk'],
            'form.keterangan' => ['nullable', 'string'],
        ]);

        return collect($validated['form'])
            ->map(fn (mixed $value): mixed => $value === '' ? null : $value)
            ->all();
    }

    private function resetForm(): void
    {
        $this->editingUmatId = null;
        $this->form = [
            'nama_lengkap' => '',
            'nama_panggilan' => '',
            'nomor_telepon' => '',
            'jenis_kelamin' => '',
            'status_perkawinan' => '',
            'hub_kk' => '',
            'golongan_darah' => '',
            'tempat_lahir' => '',
            'tanggal_lahir' => '',
            'alamat' => '',
            'kemah_id' => '',
            'area_id' => '',
            'keluarga_id' => '',
            'pendidikan' => '',
            'pekerjaan' => '',
            'domisili' => '',
            'status' => Umat::STATUS_CALON,
            'tanggal_masuk' => now()->toDateString(),
            'tanggal_keluar' => '',
            'keterangan' => '',
        ];
        $this->resetValidation();
    }

    private function normalizeForm(): void
    {
        $this->form = collect($this->form)
            ->map(fn (mixed $value): mixed => $value === '' ? null : $value)
            ->all();
    }
}; ?>

<section class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div class="space-y-1">
            <flux:heading size="xl" level="1">{{ __('Umat') }}</flux:heading>
            <flux:text>{{ __('Manage personal, family, area, and kemah data for umat records.') }}</flux:text>
        </div>

        <div class="flex w-full flex-col gap-3 sm:flex-row md:w-auto">
            <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" type="search" placeholder="{{ __('Search umat') }}" class="sm:w-80" />

            @can('manage-data')
                <div class="flex items-end gap-2">
                    <flux:button variant="filled" icon="arrow-down-tray" wire:click="export">
                        {{ __('Export Excel') }}
                    </flux:button>

                    <flux:button variant="primary" wire:click="openCreateModal">
                        {{ __('Create umat') }}
                    </flux:button>
                </div>
            @endcan
        </div>
    </div>

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-wrap items-center gap-6">
            <flux:switch wire:model.live="showArchived" :label="__('Show archive (left/deceased)')" />

            <div class="inline-flex items-center gap-2">
                <flux:switch wire:model.live="onlyCalonMatang" :label="__('Prospects ready (>= 6 months)')" />
                @if ($this->calonMatangCount > 0)
                    <flux:badge size="sm" color="amber">{{ $this->calonMatangCount }}</flux:badge>
                @endif
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-3 rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-zinc-900 lg:flex-row lg:items-end lg:gap-4">
        <flux:select wire:model.live="filterAreaId" :label="__('Area')" class="lg:w-56">
            <flux:select.option value="">{{ __('All areas') }}</flux:select.option>
            @foreach ($this->areas as $area)
                <flux:select.option wire:key="filter-area-{{ $area->id }}" value="{{ $area->id }}">{{ $area->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="filterKelompokUsia" :label="__('Age group')" class="lg:w-48">
            <flux:select.option value="">{{ __('All ages') }}</flux:select.option>
            @foreach (\App\Models\Umat::kelompokUsiaList() as $kelompok)
                <flux:select.option wire:key="filter-usia-{{ $kelompok }}" value="{{ $kelompok }}">{{ __(ucfirst($kelompok)) }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="filterBulanUlangTahun" :label="__('Birthday month')" class="lg:w-48">
            <flux:select.option value="">{{ __('All months') }}</flux:select.option>
            @foreach (range(1, 12) as $bulan)
                <flux:select.option wire:key="filter-bulan-{{ $bulan }}" value="{{ $bulan }}">{{ \Illuminate\Support\Carbon::create()->month($bulan)->translatedFormat('F') }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex items-center gap-2">
            <flux:button variant="filled" wire:click="filterUlangTahunBulanIni">
                {{ __('Birthdays this month') }}
            </flux:button>

            @if ($filterAreaId || $filterBulanUlangTahun || $filterKelompokUsia)
                <flux:button variant="ghost" wire:click="resetFilters">
                    {{ __('Reset filters') }}
                </flux:button>
            @endif
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-700">
                <thead class="bg-zinc-50 text-start text-xs font-medium uppercase text-zinc-500 dark:bg-zinc-950/60 dark:text-zinc-400">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-start">{{ __('Name') }}</th>
                        <th scope="col" class="px-4 py-3 text-start">{{ __('Phone') }}</th>
                        <th scope="col" class="px-4 py-3 text-start">{{ __('Area') }}</th>
                        <th scope="col" class="px-4 py-3 text-start">{{ __('Kemah') }}</th>
                        <th scope="col" class="px-4 py-3 text-start">{{ __('Keluarga') }}</th>
                        <th scope="col" class="px-4 py-3 text-start">{{ __('Domisili') }}</th>
                        <th scope="col" class="px-4 py-3 text-start">{{ __('Status') }}</th>
                        <th scope="col" class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse ($this->umat as $umatItem)
                        <tr wire:key="umat-{{ $umatItem->id }}">
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-950 dark:text-zinc-50">
                                    <span class="text-zinc-500 dark:text-zinc-400">{{ $umatItem->pemanggilan }}</span>
                                    {{ $umatItem->nama_lengkap ?: '-' }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $umatItem->nama_panggilan ?: '-' }}
                                    @if ($umatItem->umur !== null)
                                        &middot; {{ $umatItem->umur }} {{ __('yr') }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umatItem->nomor_telepon ?: '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umatItem->area?->name ?: '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umatItem->kemah?->name ?: '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umatItem->keluarga?->no_keluarga ?: '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umatItem->domisili ?: '-' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColor = match ($umatItem->status) {
                                        \App\Models\Umat::STATUS_AKTIF => 'green',
                                        \App\Models\Umat::STATUS_CALON => 'amber',
                                        \App\Models\Umat::STATUS_KELUAR => 'zinc',
                                        \App\Models\Umat::STATUS_MENINGGAL => 'red',
                                        default => 'zinc',
                                    };
                                @endphp
                                <flux:badge size="sm" :color="$statusColor">{{ __(ucfirst($umatItem->status)) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    @can('manage-data')
                                        @if ($umatItem->status === \App\Models\Umat::STATUS_CALON)
                                            <flux:button size="sm" variant="primary" wire:click="promoteToAktif({{ $umatItem->id }})" wire:confirm="{{ __('Promote this prospect to active?') }}">
                                                {{ __('Promote') }}
                                            </flux:button>
                                        @endif

                                        <flux:button size="sm" variant="filled" wire:click="openEditModal({{ $umatItem->id }})">
                                            {{ __('Edit') }}
                                        </flux:button>

                                        <flux:button size="sm" variant="danger" wire:click="deleteUmat({{ $umatItem->id }})" wire:confirm="{{ __('Delete this umat?') }}">
                                            {{ __('Delete') }}
                                        </flux:button>
                                    @else
                                        <span class="text-xs text-zinc-400">{{ __('View only') }}</span>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('No umat found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->umat->hasPages())
            <div class="border-t border-neutral-200 px-4 py-3 dark:border-neutral-700">
                {{ $this->umat->links() }}
            </div>
        @endif
    </div>

    <flux:modal wire:model="showFormModal" class="max-w-6xl">
        <form wire:submit="saveUmat" class="flex max-h-[82vh] flex-col">
            <div class="border-b border-neutral-200 px-1 pb-5 dark:border-neutral-700">
                <div class="space-y-1">
                    <flux:heading size="lg">{{ $editingUmatId ? __('Edit umat') : __('Create umat') }}</flux:heading>
                    <flux:text>{{ __('Fill the profile, relationship, and domicile data for this umat.') }}</flux:text>
                </div>
            </div>

            <div class="-mx-1 flex-1 overflow-y-auto px-1 py-6 [scrollbar-width:thin] [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-zinc-400/70 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-600 [&::-webkit-scrollbar-track]:bg-transparent">
                <div class="grid gap-6 lg:grid-cols-[13rem_1fr]">
                    <div class="space-y-1">
                        <flux:heading>{{ __('Data utama') }}</flux:heading>
                        <flux:text>{{ __('Identitas dan kontak umat.') }}</flux:text>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <flux:field class="md:col-span-2">
                            <flux:label>
                                {{ __('Nama lengkap') }}
                                <span class="text-brand-red-500">*</span>
                            </flux:label>
                            <flux:input wire:model="form.nama_lengkap" type="text" required autofocus />
                            <flux:error name="form.nama_lengkap" />
                        </flux:field>
                        <flux:input wire:model="form.nama_panggilan" :label="__('Panggilan')" type="text" />
                        <flux:input wire:model="form.nomor_telepon" :label="__('HP')" type="text" />

                        <flux:select wire:model="form.jenis_kelamin" :label="__('P/L')" required>
                            <flux:select.option value="">{{ __('-') }}</flux:select.option>
                            <flux:select.option value="P">{{ __('P') }}</flux:select.option>
                            <flux:select.option value="L">{{ __('L') }}</flux:select.option>
                            <flux:error name="form.jenis_kelamin" />
                        </flux:select>

                        <flux:select wire:model="form.status_perkawinan" :label="__('Status')" required>
                            <flux:select.option value="">{{ __('-') }}</flux:select.option>
                            <flux:select.option value="Belum Kawin">{{ __('Belum Kawin') }}</flux:select.option>
                            <flux:select.option value="Kawin">{{ __('Kawin') }}</flux:select.option>
                            <flux:select.option value="Cerai Hidup">{{ __('Cerai Hidup') }}</flux:select.option>
                            <flux:select.option value="Cerai Mati">{{ __('Cerai Mati') }}</flux:select.option>
                            <flux:error name="form.status_perkawinan" />
                        </flux:select>

                        <flux:select wire:model="form.golongan_darah" :label="__('Gol Dar')">
                            <flux:select.option value="">{{ __('-') }}</flux:select.option>
                            <flux:select.option value="A">{{ __('A') }}</flux:select.option>
                            <flux:select.option value="B">{{ __('B') }}</flux:select.option>
                            <flux:select.option value="AB">{{ __('AB') }}</flux:select.option>
                            <flux:select.option value="O">{{ __('O') }}</flux:select.option>
                        </flux:select>
                    </div>
                </div>

                <flux:separator class="my-6" />

                <div class="grid gap-6 lg:grid-cols-[13rem_1fr]">
                    <div class="space-y-1">
                        <flux:heading>{{ __('Relasi jemaat') }}</flux:heading>
                        <flux:text>{{ __('Hubungkan umat dengan area, kemah, dan keluarga.') }}</flux:text>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <flux:select wire:model="form.area_id" :label="__('Area')" class="xl:col-span-2">
                            <flux:select.option value="">{{ __('-') }}</flux:select.option>
                            @foreach ($this->areas as $area)
                                <flux:select.option wire:key="umat-area-{{ $area->id }}" value="{{ $area->id }}">{{ $area->name }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="form.kemah_id" :label="__('Kemah')" class="xl:col-span-2">
                            <flux:select.option value="">{{ __('-') }}</flux:select.option>
                            @foreach ($this->kemahOptions as $kemah)
                                <flux:select.option wire:key="umat-kemah-{{ $kemah->id }}" value="{{ $kemah->id }}">{{ $kemah->name }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="form.keluarga_id" :label="__('Keluarga')" class="xl:col-span-2">
                            <flux:select.option value="">{{ __('-') }}</flux:select.option>
                            @foreach ($this->keluargaOptions as $keluarga)
                                <flux:select.option wire:key="umat-keluarga-{{ $keluarga->id }}" value="{{ $keluarga->id }}">{{ $keluarga->no_keluarga }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:input wire:model="form.hub_kk" :label="__('Hub KK')" type="text" class="xl:col-span-2" />
                    </div>
                </div>

                <flux:separator class="my-6" />

                <div class="grid gap-6 lg:grid-cols-[13rem_1fr]">
                    <div class="space-y-1">
                        <flux:heading>{{ __('Data tambahan') }}</flux:heading>
                        <flux:text>{{ __('Kelahiran, pendidikan, dan pekerjaan.') }}</flux:text>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <flux:input wire:model="form.tempat_lahir" :label="__('Tempat lahir')" type="text" />
                        <flux:input wire:model="form.tanggal_lahir" :label="__('Tanggal lahir')" type="date" required />
                        <flux:input wire:model="form.pendidikan" :label="__('Pendidikan')" type="text" />
                        <flux:input wire:model="form.pekerjaan" :label="__('Pekerjaan')" type="text" />
                    </div>
                </div>

                <flux:separator class="my-6" />

                <div class="grid gap-6 lg:grid-cols-[13rem_1fr]">
                    <div class="space-y-1">
                        <flux:heading>{{ __('Status & lifecycle') }}</flux:heading>
                        <flux:text>{{ __('Status keanggotaan dan tanggal masuk/keluar jemaat.') }}</flux:text>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <flux:select wire:model="form.status" :label="__('Status')">
                            <flux:select.option value="calon">{{ __('Calon') }}</flux:select.option>
                            <flux:select.option value="aktif">{{ __('Aktif') }}</flux:select.option>
                            <flux:select.option value="keluar">{{ __('Keluar') }}</flux:select.option>
                            <flux:select.option value="meninggal">{{ __('Meninggal') }}</flux:select.option>
                            <flux:error name="form.status" />
                        </flux:select>

                        <flux:input wire:model="form.tanggal_masuk" :label="__('Tanggal masuk')" type="date" />
                        <flux:input wire:model="form.tanggal_keluar" :label="__('Tanggal keluar')" type="date" />

                        <flux:textarea wire:model="form.keterangan" :label="__('Keterangan')" rows="2" class="md:col-span-2 xl:col-span-3" />
                    </div>
                </div>

                <flux:separator class="my-6" />

                <div class="grid gap-6 lg:grid-cols-[13rem_1fr]">
                    <div class="space-y-1">
                        <flux:heading>{{ __('Domisili') }}</flux:heading>
                        <flux:text>{{ __('Alamat tinggal dan domisili saat ini.') }}</flux:text>
                    </div>

                    <div class="grid gap-4">
                        <flux:input wire:model="form.domisili" :label="__('Domisili')" type="text" />
                        <flux:textarea wire:model="form.alamat" :label="__('Alamat')" rows="3" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-neutral-200 px-1 pt-5 dark:border-neutral-700">
                <flux:button type="button" variant="filled" wire:click="closeFormModal">
                    {{ __('Cancel') }}
                </flux:button>

                <flux:button variant="primary" type="submit">
                    {{ $editingUmatId ? __('Save changes') : __('Create') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
