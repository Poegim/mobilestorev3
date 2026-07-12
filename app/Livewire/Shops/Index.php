<?php

namespace App\Livewire\Shops;

use App\Enums\UserPrivilege;
use App\Models\Shop;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination, WithFileUploads;

    public string $search = '';
    public bool $showArchived = false;

    // Form modal state.
    public bool $showForm = false;
    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public string $short_name = '';  // required + unique handled in rules()
    public string $slug = '';        // auto from short_name, unique handled in rules()
    public bool $slugTouched = false; // stops auto-sync once user edits slug

    #[Validate('nullable|string|max:255')]
    public string $description = '';

    #[Validate('email|max:64')]
    public string $email = '';

    #[Validate('string|max:32')]
    public string $phone = '';

    #[Validate('required|regex:/^#[0-9A-Fa-f]{6}$/')]
    public string $color = '#000000';

    #[Validate('string|max:32')]
    public string $address_street = '';

    #[Validate('string|max:8')]
    public string $address_building_number = '';

    #[Validate('string|max:8')]
    public string $address_apartment_number = '';

    #[Validate('regex:/^\d{2}-\d{3}$/')]
    public string $address_postal_code = '';

    #[Validate('string|max:32')]
    public string $address_city = '';

    #[Validate('integer|min:0')]
    public int $order = 0;

    #[Validate('nullable|image|max:2048')] // 2 MB
    public ?TemporaryUploadedFile $avatar = null;
    public ?string $currentAvatar = null;

    #[Validate('array')]
    public array $assignedUsers = [];

    // Shop management is admin-only.
    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    protected function rules(): array
    {
        return [
            'short_name' => ['required', 'string', 'max:32', Rule::unique('shops', 'short_name')->ignore($this->editingId)],
            'slug'       => ['required', 'string', 'max:255', Rule::unique('shops', 'slug')->ignore($this->editingId)],
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedShowArchived(): void
    {
        $this->resetPage();
    }

    // Auto-fill slug from short_name until the user edits the slug directly.
    public function updatedShortName(): void
    {
        if (! $this->slugTouched) {
            $this->slug = Str::slug($this->short_name);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugTouched = true;
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(Shop $shop): void
    {
        $this->editingId = $shop->id;
        $this->name = $shop->name;
        $this->short_name = $shop->short_name ?? '';
        $this->slug = $shop->slug ?? '';
        $this->description = $shop->description ?? '';
        $this->email = $shop->email;
        $this->phone = $shop->phone;
        $this->color = $shop->color;
        $this->address_street = $shop->address_street;
        $this->address_building_number = $shop->address_building_number;
        $this->address_apartment_number = $shop->address_apartment_number ?? '';
        $this->address_postal_code = $shop->address_postal_code;
        $this->address_city = $shop->address_city;
        $this->order = $shop->order;

        // Legacy rows (null slug) will regenerate from short_name; existing slugs stay put.
        $this->slugTouched = filled($shop->slug);
        $this->currentAvatar = $shop->avatar;
        $this->avatar = null;
        $this->assignedUsers = $shop->users()->pluck('users.id')->all();

        $this->showForm = true;
    }

    // Live preview: new upload if present, otherwise the stored photo.
    #[Computed]
    public function avatarPreview(): ?string
    {
        if ($this->avatar) {
            return $this->avatar->temporaryUrl();
        }

        return $this->currentAvatar
            ? Storage::disk('public')->url($this->currentAvatar)
            : null;
    }

    public function save(): void
    {
        // Normalize slug before validation: keep user's value, otherwise derive from short_name.
        $this->short_name = trim($this->short_name);
        $this->slug = $this->slug !== ''
            ? Str::slug($this->slug)
            : ($this->short_name !== '' ? $this->generateSlug($this->short_name) : '');

        $data = $this->validate();
        $values = collect($data)->except(['assignedUsers', 'avatar'])->all();

        // Store a freshly uploaded photo and drop the previous one.
        if ($this->avatar) {
            $values['avatar'] = $this->avatar->store('shops', 'public');

            if ($this->currentAvatar) {
                Storage::disk('public')->delete($this->currentAvatar);
            }
        }

        $shop = $this->editingId
            ? tap(Shop::findOrFail($this->editingId))->update($values)
            : Shop::create($values);

        $shop->users()->sync($this->assignedUsers);

        $this->showForm = false;
        $this->resetForm();

        Flux::toast('Sklep zapisany.', variant: 'success');
    }

    // Build a URL-friendly slug unique across shops (skips the edited record).
    protected function generateSlug(string $base): string
    {
        $base = Str::slug($base) ?: 'sklep';
        $slug = $base;
        $i = 2;

        while (
            Shop::where('slug', $slug)
                ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function toggleArchive(Shop $shop): void
    {
        $shop->update(['archive' => ! $shop->archive]);
    }

    public function delete(Shop $shop): void
    {
        if ($shop->avatar) {
            Storage::disk('public')->delete($shop->avatar);
        }

        $shop->users()->detach();
        $shop->delete();

        Flux::toast('Sklep usunięty.', variant: 'success');
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'name', 'short_name', 'slug', 'slugTouched', 'description',
            'email', 'phone', 'color',
            'address_street', 'address_building_number', 'address_apartment_number',
            'address_postal_code', 'address_city', 'order',
            'avatar', 'currentAvatar', 'assignedUsers',
        ]);
        $this->color = '#000000';
    }

    public function updateOrder(int $shopId, int $order): void
    {
        Shop::where('id', $shopId)->update(['order' => $order]);
        $this->dispatch('order-updated');
    }

    public function render()
    {
        $shops = Shop::query()
            ->when(! $this->showArchived, fn ($q) => $q->where('archive', false))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('short_name', 'like', "%{$this->search}%")
                        ->orWhere('address_city', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('order')
            ->orderBy('name')
            ->paginate(20);

        // Only regular staff (1–3) are assignable: admins/root have every shop by default,
        // blocked users (0) have no access at all.
        $users = User::whereBetween('privilege', [
                UserPrivilege::User1->value,
                UserPrivilege::User3->value,
            ])
            ->orderBy('login')
            ->get();

        return view('livewire.shops.index', [
            'shops' => $shops,
            'users' => $users,
        ]);
    }
}