<?php

use App\Models\Keluarga;
use App\Models\Umat;
use App\Services\KeluargaExcelImporter;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Title('Keluarga')] class extends Component {
    use WithFileUploads;
    use WithPagination;

    public string $search = '';
    public bool $showFormModal = false;
    public bool $showImportModal = false;
    public ?int $editingKeluargaId = null;

    public ?TemporaryUploadedFile $importFile = null;

    /**
     * @var array<string, mixed>
     */
    public array $form = [
        'no_keluarga' => '',
    ];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $memberRows = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->form['no_keluarga'] = $this->nextNoKeluarga();
        $this->addMemberRow();
        $this->showFormModal = true;
    }

    public function openImportModal(): void
    {
        $this->resetImportForm();
        $this->showImportModal = true;
    }

    public function openEditModal(int $keluargaId): void
    {
        $keluarga = Keluarga::query()
            ->with(['umat' => fn ($query) => $query->orderBy('nama_lengkap')])
            ->findOrFail($keluargaId);

        $this->editingKeluargaId = $keluarga->id;
        $this->form = [
            'no_keluarga' => $keluarga->no_keluarga,
        ];
        $this->memberRows = $keluarga->umat
            ->map(fn (Umat $umat): array => [
                'mode' => 'existing',
                'umat_id' => $umat->id,
                'nama_lengkap' => '',
                'nama_panggilan' => '',
                'nomor_telepon' => '',
                'jenis_kelamin' => '',
                'hub_kk' => '',
            ])
            ->values()
            ->all();

        if ($this->memberRows === []) {
            $this->addMemberRow();
        }

        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function addMemberRow(string $mode = 'existing'): void
    {
        $this->memberRows[] = [
            'mode' => $mode,
            'umat_id' => '',
            'nama_lengkap' => '',
            'nama_panggilan' => '',
            'nomor_telepon' => '',
            'jenis_kelamin' => '',
            'hub_kk' => '',
        ];
    }

    public function useExistingUmat(int $index): void
    {
        $this->memberRows[$index]['mode'] = 'existing';
        $this->memberRows[$index]['nama_lengkap'] = '';
        $this->memberRows[$index]['nama_panggilan'] = '';
        $this->memberRows[$index]['nomor_telepon'] = '';
        $this->memberRows[$index]['jenis_kelamin'] = '';
        $this->memberRows[$index]['hub_kk'] = '';
    }

    public function createNewUmat(int $index): void
    {
        $this->memberRows[$index]['mode'] = 'create';
        $this->memberRows[$index]['umat_id'] = '';
    }

    public function removeMemberRow(int $index): void
    {
        unset($this->memberRows[$index]);

        $this->memberRows = array_values($this->memberRows);

        if ($this->memberRows === []) {
            $this->addMemberRow();
        }
    }

    public function saveKeluarga(): void
    {
        Gate::authorize('manage-data');

        $this->normalizeRows();

        $validated = $this->validateForm();

        DB::transaction(function () use ($validated): void {
            $keluarga = $this->editingKeluargaId
                ? Keluarga::query()->findOrFail($this->editingKeluargaId)
                : new Keluarga();

            $keluarga->fill($validated['form']);
            $keluarga->save();

            $existingUmatIds = collect($validated['memberRows'])
                ->where('mode', 'existing')
                ->pluck('umat_id')
                ->filter()
                ->unique()
                ->values();

            $newUmatIds = collect($validated['memberRows'])
                ->where('mode', 'create')
                ->map(function (array $row) use ($keluarga): int {
                    return Umat::create([
                        'keluarga_id' => $keluarga->id,
                        'nama_lengkap' => $row['nama_lengkap'],
                        'nama_panggilan' => $row['nama_panggilan'] ?? null,
                        'nomor_telepon' => $row['nomor_telepon'] ?? null,
                        'jenis_kelamin' => $row['jenis_kelamin'] ?? null,
                        'hub_kk' => $row['hub_kk'] ?? null,
                    ])->id;
                });

            $memberIds = $existingUmatIds->merge($newUmatIds)->unique()->values();

            if ($this->editingKeluargaId) {
                Umat::query()
                    ->where('keluarga_id', $keluarga->id)
                    ->whereNotIn('id', $memberIds)
                    ->update(['keluarga_id' => null]);
            }

            if ($memberIds->isNotEmpty()) {
                Umat::query()
                    ->whereIn('id', $memberIds)
                    ->update(['keluarga_id' => $keluarga->id]);
            }
        });

        Flux::toast(variant: 'success', text: $this->editingKeluargaId ? __('Keluarga updated.') : __('Keluarga created.'));

        $this->closeFormModal();
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->resetImportForm();
    }

    public function deleteKeluarga(int $keluargaId): void
    {
        Gate::authorize('manage-data');

        DB::transaction(function () use ($keluargaId): void {
            Umat::query()
                ->where('keluarga_id', $keluargaId)
                ->update(['keluarga_id' => null]);

            Keluarga::query()->findOrFail($keluargaId)->delete();
        });

        Flux::toast(variant: 'success', text: __('Keluarga deleted.'));
    }

    public function importKeluarga(KeluargaExcelImporter $importer): void
    {
        Gate::authorize('manage-data');

        $this->validate([
            'importFile' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ]);

        $result = $importer->import($this->importFile->getRealPath());

        $this->resetPage();
        $this->closeImportModal();

        Flux::toast(
            variant: 'success',
            text: __('Imported :umat umat into :keluarga keluarga.', [
                'umat' => $result['umat'],
                'keluarga' => $result['keluarga'],
            ])
        );
    }

    #[Computed]
    public function keluarga(): LengthAwarePaginator
    {
        return Keluarga::query()
            ->with(['umat' => fn ($query) => $query->orderBy('nama_lengkap')])
            ->withCount('umat')
            ->when($this->search !== '', function (Builder $query): void {
                $query
                    ->where('no_keluarga', 'like', '%'.$this->search.'%')
                    ->orWhereHas('umat', fn (Builder $query) => $query->where('nama_lengkap', 'like', '%'.$this->search.'%'));
            })
            ->orderBy('no_keluarga')
            ->paginate(10);
    }

    #[Computed]
    public function umatOptions(): Collection
    {
        return Umat::query()
            ->with('keluarga')
            ->orderBy('nama_lengkap')
            ->get();
    }

    /**
     * @return array{form: array<string, mixed>, memberRows: array<int, array<string, mixed>>}
     */
    private function validateForm(): array
    {
        $validated = $this->validate([
            'form.no_keluarga' => ['required', 'string', 'max:255'],
            'memberRows' => ['array'],
            'memberRows.*.mode' => ['required', 'in:existing,create'],
            'memberRows.*.umat_id' => ['nullable', 'exists:umat,id'],
            'memberRows.*.nama_lengkap' => ['nullable', 'string', 'max:255'],
            'memberRows.*.nama_panggilan' => ['nullable', 'string', 'max:255'],
            'memberRows.*.nomor_telepon' => ['nullable', 'string', 'max:255'],
            'memberRows.*.jenis_kelamin' => ['nullable', Rule::in(Umat::jenisKelaminList())],
            'memberRows.*.hub_kk' => ['nullable', 'in:Kepala Keluarga,Istri,Anak'],
        ]);

        $messages = [];

        foreach ($validated['memberRows'] as $index => $row) {
            if ($row['mode'] === 'existing' && blank($row['umat_id'])) {
                $messages["memberRows.{$index}.umat_id"] = __('Select an existing umat.');
            }

            if ($row['mode'] === 'create' && blank($row['nama_lengkap'])) {
                $messages["memberRows.{$index}.nama_lengkap"] = __('Nama lengkap is required.');
            }

            if ($row['mode'] === 'create' && blank($row['jenis_kelamin'])) {
                $messages["memberRows.{$index}.jenis_kelamin"] = __('Gender is required.');
            }
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }

        return $validated;
    }

    private function nextNoKeluarga(): string
    {
        $latestNoKeluarga = Keluarga::query()
            ->where('no_keluarga', 'like', 'KK-%')
            ->orderByDesc('no_keluarga')
            ->value('no_keluarga');

        $lastNumber = 0;

        if (is_string($latestNoKeluarga) && preg_match('/^KK-(\d+)$/', $latestNoKeluarga, $matches) === 1) {
            $lastNumber = (int) $matches[1];
        }

        return 'KK-'.str_pad((string) ($lastNumber + 1), 5, '0', STR_PAD_LEFT);
    }

    private function resetForm(): void
    {
        $this->editingKeluargaId = null;
        $this->form = [
            'no_keluarga' => '',
        ];
        $this->memberRows = [];
        $this->resetValidation();
    }

    private function resetImportForm(): void
    {
        $this->importFile = null;
        $this->resetValidation('importFile');
    }

    private function normalizeRows(): void
    {
        $this->form = collect($this->form)
            ->map(fn (mixed $value): mixed => $value === '' ? null : $value)
            ->all();
        $this->memberRows = collect($this->memberRows)
            ->map(fn (array $row): array => collect($row)
                ->map(fn (mixed $value): mixed => $value === '' ? null : $value)
                ->all())
            ->values()
            ->all();
    }
}; ?>

<section class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div class="space-y-1">
            <flux:heading size="xl" level="1">{{ __('Keluarga') }}</flux:heading>
            <flux:text>{{ __('Manage family numbers and assign umat members.') }}</flux:text>
        </div>

        <div class="flex w-full flex-col gap-3 sm:flex-row md:w-auto">
            <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" type="search" placeholder="{{ __('Search keluarga') }}" class="sm:w-80" />

            @can('manage-data')
                <div class="flex items-end gap-2">
                    <flux:button variant="filled" wire:click="openImportModal">
                        {{ __('Import Excel') }}
                    </flux:button>

                    <flux:button variant="primary" wire:click="openCreateModal">
                        {{ __('Create keluarga') }}
                    </flux:button>
                </div>
            @endcan
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-700">
                <thead class="bg-zinc-50 text-start text-xs font-medium uppercase text-zinc-500 dark:bg-zinc-950/60 dark:text-zinc-400">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-start">{{ __('No keluarga') }}</th>
                        <th scope="col" class="px-4 py-3 text-start">{{ __('Umat') }}</th>
                        <th scope="col" class="px-4 py-3 text-start">{{ __('Members') }}</th>
                        <th scope="col" class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse ($this->keluarga as $keluargaItem)
                        <tr wire:key="keluarga-{{ $keluargaItem->id }}">
                            <td class="px-4 py-3 font-medium text-zinc-950 dark:text-zinc-50">{{ $keluargaItem->no_keluarga }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $keluargaItem->umat_count }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $keluargaItem->umat->pluck('nama_lengkap')->filter()->take(3)->join(', ') ?: '-' }}
                                @if ($keluargaItem->umat_count > 3)
                                    {{ __('and :count more', ['count' => $keluargaItem->umat_count - 3]) }}
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" variant="filled" :href="route('keluarga.pdf', $keluargaItem)" target="_blank">
                                        {{ __('PDF') }}
                                    </flux:button>

                                    @can('manage-data')
                                        <flux:button size="sm" variant="filled" wire:click="openEditModal({{ $keluargaItem->id }})">
                                            {{ __('Edit') }}
                                        </flux:button>

                                        <flux:button size="sm" variant="danger" wire:click="deleteKeluarga({{ $keluargaItem->id }})" wire:confirm="{{ __('Delete this keluarga? Umat members will remain, but no longer be assigned to this keluarga.') }}">
                                            {{ __('Delete') }}
                                        </flux:button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('No keluarga found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->keluarga->hasPages())
            <div class="border-t border-neutral-200 px-4 py-3 dark:border-neutral-700">
                {{ $this->keluarga->links() }}
            </div>
        @endif
    </div>

    <flux:modal wire:model="showFormModal" class="max-w-[96rem]">
        <form wire:submit="saveKeluarga" class="flex max-h-[82vh] flex-col">
            <div class="border-b border-neutral-200 px-1 pb-5 dark:border-neutral-700">
                <div class="space-y-1">
                    <flux:heading size="lg">{{ $editingKeluargaId ? __('Edit keluarga') : __('Create keluarga') }}</flux:heading>
                    <flux:text>{{ __('Add existing umat or create new umat records as family members.') }}</flux:text>
                </div>
            </div>

            <div class="-mx-1 flex-1 overflow-y-auto px-1 py-6 [scrollbar-width:thin] [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-zinc-400/70 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-600 [&::-webkit-scrollbar-track]:bg-transparent">
                <div class="grid gap-5 lg:grid-cols-[14rem_1fr]">

                    <flux:field class="max-w-md">
                        <flux:label>
                            {{ __('No keluarga') }}
                            <span class="text-brand-red-500">*</span>
                        </flux:label>
                        <flux:input wire:model="form.no_keluarga" type="text" description="{{ __('Auto generated') }}" readonly required />
                        <flux:error name="form.no_keluarga" />
                    </flux:field>
                </div>

                <flux:separator class="my-6" />

                <div class="space-y-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="space-y-1">
                            <flux:heading>{{ __('Umat members') }}</flux:heading>
                            <flux:text>{{ __('Add existing umat or create new umat records as family members.') }}</flux:text>
                        </div>

                        <div class="flex gap-2">
                            <flux:button type="button" size="sm" variant="filled" wire:click="addMemberRow('existing')">
                                {{ __('Add existing') }}
                            </flux:button>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="hidden grid-cols-[10rem_minmax(20rem,1.5fr)_10rem_13rem_5rem] gap-3 px-3 text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400 xl:grid">
                            <div>{{ __('Jenis') }}</div>
                            <div>{{ __('Nama lengkap') }}</div>
                            <div>{{ __('Gender') }}</div>
                            <div>{{ __('Hub KK') }}</div>
                            <div class="text-end">{{ __('Actions') }}</div>
                        </div>

                        @foreach ($memberRows as $index => $row)
                            @php
                                $selectedUmat = (($row['mode'] ?? 'existing') === 'existing')
                                    ? $this->umatOptions->firstWhere('id', (int) ($row['umat_id'] ?? 0))
                                    : null;
                            @endphp

                            <div wire:key="keluarga-member-row-{{ $index }}" class="grid gap-3 rounded-lg border border-neutral-200 p-3 dark:border-neutral-700 xl:grid-cols-[10rem_minmax(20rem,1.5fr)_10rem_13rem_5rem] xl:items-start">
                                <flux:select wire:model.live="memberRows.{{ $index }}.mode" :label="__('Jenis')" label:sr-only size="sm">
                                    <flux:select.option value="existing">{{ __('Data terdaftar') }}</flux:select.option>
                                    <flux:select.option value="create">{{ __('Data baru') }}</flux:select.option>
                                </flux:select>

                                @if (($row['mode'] ?? 'existing') === 'existing')
                                    <div>
                                        <flux:select wire:model.live="memberRows.{{ $index }}.umat_id" :label="__('Nama lengkap')" label:sr-only size="sm">
                                            <flux:select.option value="">{{ __('Select umat') }}</flux:select.option>
                                            @foreach ($this->umatOptions as $umat)
                                                <flux:select.option wire:key="keluarga-umat-option-{{ $index }}-{{ $umat->id }}" value="{{ $umat->id }}">
                                                    {{ $umat->nama_lengkap ?: __('Unnamed umat') }}
                                                    @if ($umat->keluarga)
                                                        - {{ $umat->keluarga->no_keluarga }}
                                                    @endif
                                                </flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </div>

                                    <flux:input :label="__('Gender')" label:sr-only type="text" size="sm" value="{{ $selectedUmat?->jenis_kelamin ? __($selectedUmat->jenis_kelamin) : '-' }}" disabled />
                                    <flux:input :label="__('Hub KK')" label:sr-only type="text" size="sm" value="{{ $selectedUmat?->hub_kk ? __($selectedUmat->hub_kk) : '-' }}" disabled />
                                @else
                                    <flux:input wire:model="memberRows.{{ $index }}.nama_lengkap" :label="__('Nama lengkap')" label:sr-only type="text" size="sm" required />

                                    <flux:select wire:model="memberRows.{{ $index }}.jenis_kelamin" :label="__('Gender')" label:sr-only size="sm" required>
                                        <flux:select.option value="">{{ __('-') }}</flux:select.option>
                                        <flux:select.option value="L">{{ __('L') }}</flux:select.option>
                                        <flux:select.option value="P">{{ __('P') }}</flux:select.option>
                                        <flux:error name="memberRows.{{ $index }}.jenis_kelamin" />
                                    </flux:select>

                                    <flux:select wire:model="memberRows.{{ $index }}.hub_kk" :label="__('Hub KK')" label:sr-only size="sm">
                                        <flux:select.option value="">{{ __('-') }}</flux:select.option>
                                        <flux:select.option value="Kepala Keluarga">{{ __('Kepala Keluarga') }}</flux:select.option>
                                        <flux:select.option value="Istri">{{ __('Istri') }}</flux:select.option>
                                        <flux:select.option value="Anak">{{ __('Anak') }}</flux:select.option>
                                    </flux:select>
                                @endif

                                <div class="flex justify-end gap-1">

                                    <flux:button type="button" size="sm" variant="danger" wire:click="removeMemberRow({{ $index }})" icon="trash" tooltip="{{ __('Remove') }}" aria-label="{{ __('Remove') }}" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-neutral-200 px-1 pt-5 dark:border-neutral-700">
                <flux:button type="button" variant="filled" wire:click="closeFormModal">
                    {{ __('Cancel') }}
                </flux:button>

                <flux:button variant="primary" type="submit">
                    {{ $editingKeluargaId ? __('Save changes') : __('Create') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showImportModal" class="max-w-xl">
        <form wire:submit="importKeluarga" class="space-y-6">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Import keluarga') }}</flux:heading>
                <flux:text>{{ __('Upload an Excel file using the KKJ layout.') }}</flux:text>
            </div>

            <flux:field>
                <flux:label>{{ __('Excel file') }}</flux:label>
                <flux:input wire:model="importFile" type="file" accept=".xlsx" />
                <flux:error name="importFile" />
            </flux:field>

            <div class="flex justify-end gap-3 border-t border-neutral-200 pt-5 dark:border-neutral-700">
                <flux:button type="button" variant="filled" wire:click="closeImportModal">
                    {{ __('Cancel') }}
                </flux:button>

                <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="importFile,importKeluarga">
                    {{ __('Import') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
