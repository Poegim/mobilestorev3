<?php
// app/Livewire/Admin/Users/Index.php

namespace App\Livewire\Admin\Users;

use App\Enums\UserPrivilege;
use App\Models\Shop;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $privilegeFilter = '';

    public function mount(): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPrivilegeFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function privilegeCases(): array
    {
        return UserPrivilege::cases();
    }

    public function toggleBlock(int $userId): void
    {
        if ($userId === auth()->id()) {
            \Flux::toast(variant: 'danger', text: 'Nie możesz zablokować własnego konta.');
            return;
        }

        $user = User::findOrFail($userId);
        $user->privilege = $user->privilege === UserPrivilege::Blocked
            ? UserPrivilege::User1
            : UserPrivilege::Blocked;
        $user->save();

        \Flux::toast(variant: 'success', text: $user->privilege === UserPrivilege::Blocked
            ? 'Użytkownik zablokowany.'
            : 'Użytkownik odblokowany.'
        );
    }

    public function render()
    {
        $query = User::with('shops')
            ->when($this->search, fn ($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhere('login', 'like', "%{$this->search}%")
            )
            ->when($this->privilegeFilter !== '', fn ($q) =>
                $q->where('privilege', (int) $this->privilegeFilter)
            )
            ->orderByDesc('privilege')
            ->orderBy('name');

        return view('livewire.admin.users.index', [
            'users' => $query->paginate(20),
        ])->title('Zarządzanie użytkownikami');
    }
}