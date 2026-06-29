<?php
// app/Livewire/Admin/Users/Create.php

namespace App\Livewire\Admin\Users;

use App\Enums\UserPrivilege;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Create extends Component
{
    public ?string $name = null;
    public ?string $email = null;
    public string $password = '';
    public string $passwordConfirmation = '';
    public int $privilege = UserPrivilege::User1->value;
    public array $selectedShops = [];

    public function mount(): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }
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
            'name'      => 'required|string|max:255',
            'email'     => 'nullable|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|same:passwordConfirmation',
            'privilege' => 'required|integer|in:' . implode(',', array_column(UserPrivilege::cases(), 'value')),
        ]);

        $user = new User();
        $user->name       = $this->name;
        $user->email      = $this->email;
        $user->password   = Hash::make($this->password);
        $user->privilege  = UserPrivilege::from($this->privilege);
        $user->contact_id = 0;
        $user->save();

        $user->shops()->sync(array_map('intval', $this->selectedShops));

        $this->redirect(route('admin.users.show', $user), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.users.create')->title('Nowy użytkownik');
    }
}