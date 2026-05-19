<?php

use App\Models\Area;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Area')] class extends Component {
    use WithPagination;

    public string $name = '';
    public string $search = '';
    public ?int $editingAreaId = null;
    public ?int $selectedAreaId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->selectedAreaId = Area::query()->orderBy('name')->value('id');
    }

    public function createArea(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        Area::create($validated);

        $this->reset('name');

        Flux::toast(variant: 'success', text: __('Area created.'));
    }

    public function editArea(int $areaId): void
    {
        $area = Area::query()->findOrFail($areaId);

        $this->selectArea($area->id);
        $this->editingAreaId = $area->id;
        $this->name = $area->name;
    }

    public function selectArea(int $areaId): void
    {
        $this->selectedAreaId = Area::query()->findOrFail($areaId)->id;
    }

    public function updateArea(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        Area::query()->findOrFail($this->editingAreaId)->update($validated);

        $this->cancelEditing();

        Flux::toast(variant: 'success', text: __('Area updated.'));
    }

    public function cancelEditing(): void
    {
        $this->reset('editingAreaId', 'name');
    }

    public function deleteArea(int $areaId): void
    {
        Area::query()->findOrFail($areaId)->delete();

        if ($this->editingAreaId === $areaId) {
            $this->cancelEditing();
        }

        if ($this->selectedAreaId === $areaId) {
            $this->selectedAreaId = Area::query()->orderBy('name')->value('id');
        }

        Flux::toast(variant: 'success', text: __('Area deleted.'));
    }

    #[Computed]
    public function areas(): LengthAwarePaginator
    {
        return Area::query()
            ->withCount('umat')
            ->when($this->search !== '', fn (Builder $query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->paginate(10);
    }

    #[Computed]
    public function selectedArea(): ?Area
    {
        if ($this->selectedAreaId === null) {
            return null;
        }

        return Area::query()->withCount('umat')->find($this->selectedAreaId);
    }

    #[Computed]
    public function selectedAreaUmat(): Collection
    {
        if ($this->selectedArea === null) {
            return collect();
        }

        return $this->selectedArea
            ->umat()
            ->with(['kemah', 'keluarga'])
            ->orderBy('nama_lengkap')
            ->get();
    }
}; ?>

<section class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div class="space-y-1">
            <flux:heading size="xl" level="1">{{ __('Areas') }}</flux:heading>
            <flux:text>{{ __('Manage area records used by umat data.') }}</flux:text>
        </div>

        <div class="w-full md:w-80">
            <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" type="search" placeholder="{{ __('Search areas') }}" />
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
                        @forelse ($this->areas as $area)
                            <tr wire:key="area-{{ $area->id }}" @class([
                                'bg-zinc-50 dark:bg-zinc-800/70' => $selectedAreaId === $area->id,
                            ])>
                                <td class="px-4 py-3 font-medium text-zinc-950 dark:text-zinc-50">
                                    <button type="button" wire:click="selectArea({{ $area->id }})" class="text-start hover:underline">
                                        {{ $area->name }}
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $area->umat_count }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <flux:button size="sm" variant="ghost" wire:click="selectArea({{ $area->id }})">
                                            {{ __('Detail') }}
                                        </flux:button>

                                        <flux:button size="sm" variant="filled" wire:click="editArea({{ $area->id }})">
                                            {{ __('Edit') }}
                                        </flux:button>

                                        <flux:button size="sm" variant="danger" wire:click="deleteArea({{ $area->id }})" wire:confirm="{{ __('Delete this area?') }}">
                                            {{ __('Delete') }}
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                    {{ __('No areas found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($this->areas->hasPages())
                <div class="border-t border-neutral-200 px-4 py-3 dark:border-neutral-700">
                    {{ $this->areas->links() }}
                </div>
            @endif
        </div>

        <form wire:submit="{{ $editingAreaId ? 'updateArea' : 'createArea' }}" class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-900">
            <div class="space-y-1">
                <flux:heading>{{ $editingAreaId ? __('Edit area') : __('Create area') }}</flux:heading>
                <flux:text>{{ __('Area names are used as relationship data for umat.') }}</flux:text>
            </div>

            <div class="mt-5 space-y-5">
                <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />

                <div class="flex items-center gap-3">
                    <flux:button variant="primary" type="submit">
                        {{ $editingAreaId ? __('Save changes') : __('Create') }}
                    </flux:button>

                    @if ($editingAreaId)
                        <flux:button type="button" variant="filled" wire:click="cancelEditing">
                            {{ __('Cancel') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
        <div class="flex flex-col gap-2 border-b border-neutral-200 p-5 dark:border-neutral-700 md:flex-row md:items-center md:justify-between">
            <div class="space-y-1">
                <flux:heading>{{ __('Area detail') }}</flux:heading>
                <flux:text>
                    @if ($this->selectedArea)
                        {{ $this->selectedArea->name }} · {{ trans_choice(':count umat|:count umat', $this->selectedArea->umat_count) }}
                    @else
                        {{ __('Select an area to see umat records.') }}
                    @endif
                </flux:text>
            </div>
        </div>

        @if ($this->selectedArea)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-700">
                    <thead class="bg-zinc-50 text-xs font-medium uppercase text-zinc-500 dark:bg-zinc-950/60 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-start">{{ __('Name') }}</th>
                            <th scope="col" class="px-4 py-3 text-start">{{ __('Phone') }}</th>
                            <th scope="col" class="px-4 py-3 text-start">{{ __('Kemah') }}</th>
                            <th scope="col" class="px-4 py-3 text-start">{{ __('Keluarga') }}</th>
                            <th scope="col" class="px-4 py-3 text-start">{{ __('Domisili') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @forelse ($this->selectedAreaUmat as $umat)
                            <tr wire:key="area-umat-{{ $umat->id }}">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-zinc-950 dark:text-zinc-50">{{ $umat->nama_lengkap ?: '-' }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $umat->nama_panggilan ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umat->nomor_telepon ?: '-' }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umat->kemah?->name ?: '-' }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umat->keluarga?->no_keluarga ?: '-' }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $umat->domisili ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                    {{ __('No umat records are assigned to this area.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                {{ __('No areas are available yet.') }}
            </div>
        @endif
    </div>
</section>
