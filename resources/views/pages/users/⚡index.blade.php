<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Pengguna')] class extends Component {
    use WithPagination;

    public string $search = '';
    public bool $showFormModal = false;
    public ?int $editingUserId = null;

    /**
     * @var array<string, mixed>
     */
    public array $form = [
        'name' => '',
        'email' => '',
        'password' => '',
        'role' => User::ROLE_PENDETA,
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        Gate::authorize('manage-data');

        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $userId): void
    {
        Gate::authorize('manage-data');

        $user = User::query()->findOrFail($userId);

        $this->editingUserId = $user->id;
        $this->form = [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'role' => $user->role,
        ];

        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function saveUser(): void
    {
        Gate::authorize('manage-data');

        $validated = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'.($this->editingUserId ? ','.$this->editingUserId : '')],
            'form.password' => [$this->editingUserId ? 'nullable' : 'required', 'string', 'min:8'],
            'form.role' => ['required', 'string', 'in:'.implode(',', User::roles())],
        ])['form'];

        if ($this->editingUserId) {
            $user = User::query()->findOrFail($this->editingUserId);
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->role = $validated['role'];

            if (filled($validated['password'])) {
                $user->password = $validated['password'];
            }

            $user->save();

            Flux::toast(variant: 'success', text: __('User updated.'));
        } else {
            User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => $validated['role'],
                'email_verified_at' => now(),
            ]);

            Flux::toast(variant: 'success', text: __('User created.'));
        }

        $this->closeFormModal();
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function deleteUser(int $userId): void
    {
        Gate::authorize('manage-data');

        if ($userId === auth()->id()) {
            Flux::toast(variant: 'danger', text: __('You cannot delete your own account.'));

            return;
        }

        User::query()->findOrFail($userId)->delete();

        Flux::toast(variant: 'success', text: __('User deleted.'));
    }

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(10);
    }

    private function resetForm(): void
    {
        $this->editingUserId = null;
        $this->form = [
            'name' => '',
            'email' => '',
            'password' => '',
            'role' => User::ROLE_PENDETA,
        ];
        $this->resetValidation();
    }
}; ?>

<section class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div class="space-y-1">
            <flux:heading size="xl" level="1">{{ __('Users') }}</flux:heading>
            <flux:text>{{ __('Manage login accounts and their access level.') }}</flux:text>
        </div>

        <div class="flex w-full flex-col gap-3 sm:flex-row md:w-auto">
            <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" type="search" placeholder="{{ __('Search users') }}" class="sm:w-80" />

            <div class="flex items-end">
                <flux:button variant="primary" wire:click="openCreateModal">
                    {{ __('Create user') }}
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
                        <th scope="col" class="px-4 py-3 text-start">{{ __('Email address') }}</th>
                        <th scope="col" class="px-4 py-3 text-start">{{ __('Role') }}</th>
                        <th scope="col" class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse ($this->users as $userItem)
                        <tr wire:key="user-{{ $userItem->id }}">
                            <td class="px-4 py-3 font-medium text-zinc-950 dark:text-zinc-50">{{ $userItem->name }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $userItem->email }}</td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" :color="$userItem->isAdmin() ? 'blue' : 'zinc'">{{ __(ucfirst($userItem->role)) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" variant="filled" wire:click="openEditModal({{ $userItem->id }})">
                                        {{ __('Edit') }}
                                    </flux:button>

                                    @if ($userItem->id !== auth()->id())
                                        <flux:button size="sm" variant="danger" wire:click="deleteUser({{ $userItem->id }})" wire:confirm="{{ __('Delete this user?') }}">
                                            {{ __('Delete') }}
                                        </flux:button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('No users found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->users->hasPages())
            <div class="border-t border-neutral-200 px-4 py-3 dark:border-neutral-700">
                {{ $this->users->links() }}
            </div>
        @endif
    </div>

    <flux:modal wire:model="showFormModal" class="max-w-lg">
        <form wire:submit="saveUser" class="space-y-6">
            <div class="space-y-1">
                <flux:heading size="lg">{{ $editingUserId ? __('Edit user') : __('Create user') }}</flux:heading>
                <flux:text>{{ __('Set the account details and access level.') }}</flux:text>
            </div>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Name') }}</flux:label>
                    <flux:input wire:model="form.name" type="text" required autofocus />
                    <flux:error name="form.name" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Email address') }}</flux:label>
                    <flux:input wire:model="form.email" type="email" required />
                    <flux:error name="form.email" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ $editingUserId ? __('New password (leave blank to keep)') : __('Password') }}</flux:label>
                    <flux:input wire:model="form.password" type="password" />
                    <flux:error name="form.password" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Role') }}</flux:label>
                    <flux:select wire:model="form.role">
                        <flux:select.option value="{{ \App\Models\User::ROLE_ADMIN }}">{{ __('Admin') }}</flux:select.option>
                        <flux:select.option value="{{ \App\Models\User::ROLE_PENDETA }}">{{ __('Pendeta') }}</flux:select.option>
                    </flux:select>
                    <flux:error name="form.role" />
                </flux:field>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="filled" wire:click="closeFormModal">
                    {{ __('Cancel') }}
                </flux:button>

                <flux:button variant="primary" type="submit">
                    {{ $editingUserId ? __('Save changes') : __('Create') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
