<?php

use App\Models\Area;
use App\Models\Keluarga;
use App\Models\Kemah;
use App\Models\Umat;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Umat')] class extends Component {
    use WithPagination;

    public string $search = '';
    public bool $showFormModal = false;
    public ?int $editingUmatId = null;

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
    ];

    public function updatedSearch(): void
    {
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
        ];

        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function saveUmat(): void
    {
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
        Umat::query()->findOrFail($umatId)->delete();

        Flux::toast(variant: 'success', text: __('Umat deleted.'));
    }

    #[Computed]
    public function umat(): LengthAwarePaginator
    {
        return Umat::query()
            ->with(['area', 'kemah', 'keluarga'])
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query
                        ->where('nama_lengkap', 'like', '%'.$this->search.'%')
                        ->orWhere('nama_panggilan', 'like', '%'.$this->search.'%')
                        ->orWhere('nomor_telepon', 'like', '%'.$this->search.'%')
                        ->orWhere('domisili', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('nama_lengkap')
            ->paginate(10);
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
            'form.jenis_kelamin' => ['nullable', 'string', 'max:1'],
            'form.status_perkawinan' => ['nullable', 'string', 'max:255'],
            'form.hub_kk' => ['nullable', 'string', 'max:255'],
            'form.golongan_darah' => ['nullable', 'string', 'max:255'],
            'form.tempat_lahir' => ['nullable', 'string', 'max:255'],
            'form.tanggal_lahir' => ['nullable', 'date'],
            'form.alamat' => ['nullable', 'string'],
            'form.kemah_id' => ['nullable', 'exists:kemah,id'],
            'form.area_id' => ['nullable', 'exists:area,id'],
            'form.keluarga_id' => ['nullable', 'exists:keluarga,id'],
            'form.pendidikan' => ['nullable', 'string', 'max:255'],
            'form.pekerjaan' => ['nullable', 'string', 'max:255'],
            'form.domisili' => ['nullable', 'string', 'max:255'],
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

            <div class="flex items-end">
                <flux:button variant="primary" wire:click="openCreateModal">
                    {{ __('Create umat') }}
                </flux:button>
            </div>
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
                        <th scope="col" class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse ($this->umat as $umatItem)
                        <tr wire:key="umat-{{ $umatItem->id }}">
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-950 dark:text-zinc-50">{{ $umatItem->nama_lengkap ?: '-' }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $umatItem->nama_panggilan ?: '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umatItem->nomor_telepon ?: '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umatItem->area?->name ?: '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umatItem->kemah?->name ?: '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umatItem->keluarga?->no_keluarga ?: '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umatItem->domisili ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" variant="filled" wire:click="openEditModal({{ $umatItem->id }})">
                                        {{ __('Edit') }}
                                    </flux:button>

                                    <flux:button size="sm" variant="danger" wire:click="deleteUmat({{ $umatItem->id }})" wire:confirm="{{ __('Delete this umat?') }}">
                                        {{ __('Delete') }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
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

    <flux:modal wire:model="showFormModal" class="max-w-5xl">
        <form wire:submit="saveUmat" class="space-y-6">
            <div class="space-y-1">
                <flux:heading size="lg">{{ $editingUmatId ? __('Edit umat') : __('Create umat') }}</flux:heading>
                <flux:text>{{ __('Fill the profile, relationship, and domicile data for this umat.') }}</flux:text>
            </div>

            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <flux:input wire:model="form.nama_lengkap" :label="__('Nama lengkap')" type="text" required autofocus />
                <flux:input wire:model="form.nama_panggilan" :label="__('Panggilan')" type="text" />
                <flux:input wire:model="form.nomor_telepon" :label="__('HP')" type="text" />

                <flux:select wire:model="form.jenis_kelamin" :label="__('P/L')">
                    <flux:select.option value="">{{ __('-') }}</flux:select.option>
                    <flux:select.option value="P">{{ __('P') }}</flux:select.option>
                    <flux:select.option value="L">{{ __('L') }}</flux:select.option>
                </flux:select>

                <flux:select wire:model="form.status_perkawinan" :label="__('Status')">
                    <flux:select.option value="">{{ __('-') }}</flux:select.option>
                    <flux:select.option value="Belum Kawin">{{ __('Belum Kawin') }}</flux:select.option>
                    <flux:select.option value="Kawin">{{ __('Kawin') }}</flux:select.option>
                    <flux:select.option value="Cerai Hidup">{{ __('Cerai Hidup') }}</flux:select.option>
                    <flux:select.option value="Cerai Mati">{{ __('Cerai Mati') }}</flux:select.option>
                </flux:select>

                <flux:input wire:model="form.hub_kk" :label="__('Hub KK')" type="text" />

                <flux:select wire:model="form.golongan_darah" :label="__('Gol Dar')">
                    <flux:select.option value="">{{ __('-') }}</flux:select.option>
                    <flux:select.option value="A">{{ __('A') }}</flux:select.option>
                    <flux:select.option value="B">{{ __('B') }}</flux:select.option>
                    <flux:select.option value="AB">{{ __('AB') }}</flux:select.option>
                    <flux:select.option value="O">{{ __('O') }}</flux:select.option>
                </flux:select>

                <flux:input wire:model="form.tempat_lahir" :label="__('Tempat lahir')" type="text" />
                <flux:input wire:model="form.tanggal_lahir" :label="__('Tanggal lahir')" type="date" />

                <flux:select wire:model="form.area_id" :label="__('Area')">
                    <flux:select.option value="">{{ __('-') }}</flux:select.option>
                    @foreach ($this->areas as $area)
                        <flux:select.option wire:key="umat-area-{{ $area->id }}" value="{{ $area->id }}">{{ $area->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="form.kemah_id" :label="__('Kemah')">
                    <flux:select.option value="">{{ __('-') }}</flux:select.option>
                    @foreach ($this->kemahOptions as $kemah)
                        <flux:select.option wire:key="umat-kemah-{{ $kemah->id }}" value="{{ $kemah->id }}">{{ $kemah->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="form.keluarga_id" :label="__('Keluarga')">
                    <flux:select.option value="">{{ __('-') }}</flux:select.option>
                    @foreach ($this->keluargaOptions as $keluarga)
                        <flux:select.option wire:key="umat-keluarga-{{ $keluarga->id }}" value="{{ $keluarga->id }}">{{ $keluarga->no_keluarga }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="form.pendidikan" :label="__('Pendidikan')" type="text" />
                <flux:input wire:model="form.pekerjaan" :label="__('Pekerjaan')" type="text" />
                <flux:input wire:model="form.domisili" :label="__('Domisili')" type="text" />
            </div>

            <flux:textarea wire:model="form.alamat" :label="__('Alamat')" rows="3" />

            <div class="flex justify-end gap-3">
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
