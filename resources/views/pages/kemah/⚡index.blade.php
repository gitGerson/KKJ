<?php

use App\Models\Kemah;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Kemah')] class extends Component {
    use WithPagination;

    public string $name = '';
    public string $search = '';
    public ?int $editingKemahId = null;
    public ?int $selectedKemahId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->selectedKemahId = Kemah::query()->orderBy('name')->value('id');
    }

    public function createKemah(): void
    {
        Gate::authorize('manage-data');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $kemah = Kemah::create($validated);

        $this->selectedKemahId = $kemah->id;
        $this->reset('name');

        Flux::toast(variant: 'success', text: __('Kemah created.'));
    }

    public function editKemah(int $kemahId): void
    {
        $kemah = Kemah::query()->findOrFail($kemahId);

        $this->selectKemah($kemah->id);
        $this->editingKemahId = $kemah->id;
        $this->name = $kemah->name;
    }

    public function selectKemah(int $kemahId): void
    {
        $this->selectedKemahId = Kemah::query()->findOrFail($kemahId)->id;
    }

    public function updateKemah(): void
    {
        Gate::authorize('manage-data');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        Kemah::query()->findOrFail($this->editingKemahId)->update($validated);

        $this->cancelEditing();

        Flux::toast(variant: 'success', text: __('Kemah updated.'));
    }

    public function cancelEditing(): void
    {
        $this->reset('editingKemahId', 'name');
    }

    public function deleteKemah(int $kemahId): void
    {
        Gate::authorize('manage-data');

        Kemah::query()->findOrFail($kemahId)->delete();

        if ($this->editingKemahId === $kemahId) {
            $this->cancelEditing();
        }

        if ($this->selectedKemahId === $kemahId) {
            $this->selectedKemahId = Kemah::query()->orderBy('name')->value('id');
        }

        Flux::toast(variant: 'success', text: __('Kemah deleted.'));
    }

    #[Computed]
    public function kemah(): LengthAwarePaginator
    {
        return Kemah::query()
            ->withCount('umat')
            ->when($this->search !== '', fn (Builder $query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->paginate(10);
    }

    #[Computed]
    public function selectedKemah(): ?Kemah
    {
        if ($this->selectedKemahId === null) {
            return null;
        }

        return Kemah::query()->withCount('umat')->find($this->selectedKemahId);
    }

    #[Computed]
    public function selectedKemahUmat(): Collection
    {
        if ($this->selectedKemah === null) {
            return collect();
        }

        return $this->selectedKemah
            ->umat()
            ->with(['area', 'keluarga'])
            ->orderBy('nama_lengkap')
            ->get();
    }
}; ?>

<section class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div class="space-y-1">
            <flux:heading size="xl" level="1">{{ __('Kemah') }}</flux:heading>
            <flux:text>{{ __('Manage kemah records used by umat data.') }}</flux:text>
        </div>

        <div class="w-full md:w-80">
            <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" type="search" placeholder="{{ __('Search kemah') }}" />
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-700">
                    <thead class="bg-zinc-50 text-start text-xs font-medium uppercase text-zinc-500 dark:bg-zinc-950/60 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-start">{{ __('Name') }}</th>
                            <th scope="col" class="px-4 py-3 text-start">{{ __('Umat') }}</th>
                            <th scope="col" class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @forelse ($this->kemah as $kemahItem)
                            <tr wire:key="kemah-{{ $kemahItem->id }}" @class([
                                'bg-zinc-50 dark:bg-zinc-800/70' => $selectedKemahId === $kemahItem->id,
                            ])>
                                <td class="px-4 py-3 font-medium text-zinc-950 dark:text-zinc-50">
                                    <button type="button" wire:click="selectKemah({{ $kemahItem->id }})" class="text-start hover:underline">
                                        {{ $kemahItem->name }}
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $kemahItem->umat_count }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <flux:button size="sm" variant="ghost" wire:click="selectKemah({{ $kemahItem->id }})">
                                            {{ __('Detail') }}
                                        </flux:button>

                                        @can('manage-data')
                                            <flux:button size="sm" variant="filled" wire:click="editKemah({{ $kemahItem->id }})">
                                                {{ __('Edit') }}
                                            </flux:button>

                                            <flux:button size="sm" variant="danger" wire:click="deleteKemah({{ $kemahItem->id }})" wire:confirm="{{ __('Delete this kemah?') }}">
                                                {{ __('Delete') }}
                                            </flux:button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                    {{ __('No kemah found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($this->kemah->hasPages())
                <div class="border-t border-neutral-200 px-4 py-3 dark:border-neutral-700">
                    {{ $this->kemah->links() }}
                </div>
            @endif
        </div>

        @can('manage-data')
        <form wire:submit="{{ $editingKemahId ? 'updateKemah' : 'createKemah' }}" class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-900">
            <div class="space-y-1">
                <flux:heading>{{ $editingKemahId ? __('Edit kemah') : __('Create kemah') }}</flux:heading>
                <flux:text>{{ __('Kemah names are used as relationship data for umat.') }}</flux:text>
            </div>

            <div class="mt-5 space-y-5">
                <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />

                <div class="flex items-center gap-3">
                    <flux:button variant="primary" type="submit">
                        {{ $editingKemahId ? __('Save changes') : __('Create') }}
                    </flux:button>

                    @if ($editingKemahId)
                        <flux:button type="button" variant="filled" wire:click="cancelEditing">
                            {{ __('Cancel') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        </form>
        @endcan
    </div>

    <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
        <div class="flex flex-col gap-2 border-b border-neutral-200 p-5 dark:border-neutral-700 md:flex-row md:items-center md:justify-between">
            <div class="space-y-1">
                <flux:heading>{{ __('Kemah detail') }}</flux:heading>
                <flux:text>
                    @if ($this->selectedKemah)
                        {{ $this->selectedKemah->name }} - {{ trans_choice(':count umat|:count umat', $this->selectedKemah->umat_count) }}
                    @else
                        {{ __('Select a kemah to see umat records.') }}
                    @endif
                </flux:text>
            </div>
        </div>

        @if ($this->selectedKemah)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-700">
                    <thead class="bg-zinc-50 text-xs font-medium uppercase text-zinc-500 dark:bg-zinc-950/60 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-start">{{ __('Name') }}</th>
                            <th scope="col" class="px-4 py-3 text-start">{{ __('Phone') }}</th>
                            <th scope="col" class="px-4 py-3 text-start">{{ __('Area') }}</th>
                            <th scope="col" class="px-4 py-3 text-start">{{ __('Keluarga') }}</th>
                            <th scope="col" class="px-4 py-3 text-start">{{ __('Domisili') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @forelse ($this->selectedKemahUmat as $umat)
                            <tr wire:key="kemah-umat-{{ $umat->id }}">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-zinc-950 dark:text-zinc-50">{{ $umat->nama_lengkap ?: '-' }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $umat->nama_panggilan ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umat->nomor_telepon ?: '-' }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umat->area?->name ?: '-' }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umat->keluarga?->no_keluarga ?: '-' }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umat->domisili ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                    {{ __('No umat records are assigned to this kemah.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                {{ __('No kemah are available yet.') }}
            </div>
        @endif
    </div>
</section>
