<?php
// app/Livewire/Admin/Users/Show.php

namespace App\Livewire\Admin\Users;

use App\Enums\UserPrivilege;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    public User $user;

    public ?string $name = null;
    public ?string $email = null;
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';
    public int $privilege = UserPrivilege::User1->value;
    public array $selectedShops = [];

    public function mount(User $user): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $this->user = $user->load('shops');

        $this->name          = $user->name;
        $this->email         = $user->email;
        $this->privilege     = $user->privilege?->value ?? UserPrivilege::Blocked->value;
        $this->selectedShops = $user->shops->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    #[Computed]
    public function allShops(): \Illuminate\Database\Eloquent\Collection
    {
        return Shop::orderBy('name')->get();
    }

    #[Computed]
    public function privilegeCases(): array
    {
        return UserPrivilege::cases();
    }

    public function save(): void
    {
        $this->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'nullable|email|max:255|unique:users,email,' . $this->user->id,
            'newPassword' => 'nullable|string|min:8|same:newPasswordConfirmation',
            'privilege'   => 'required|integer|in:' . implode(',', array_column(UserPrivilege::cases(), 'value')),
        ]);

        $this->user->name      = $this->name;
        $this->user->email     = $this->email;
        $this->user->privilege = UserPrivilege::from($this->privilege);

        if ($this->newPassword !== '') {
            $this->user->password = Hash::make($this->newPassword);
            $this->newPassword    = '';
        }

        $this->user->save();
        $this->user->shops()->sync(array_map('intval', $this->selectedShops));

        \Flux::toast(variant: 'success', text: 'Zapisano.');
    }

    public function render()
    {
        return view('livewire.admin.users.show')->title('Edycja użytkownika');
    }
}