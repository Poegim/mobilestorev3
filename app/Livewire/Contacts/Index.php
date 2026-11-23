<?php

namespace App\Livewire\Contacts;

use App\Models\Contact;
use Flux\Flux;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public int $perPage = 25;

    /** Null = creating a new contact, otherwise the id being edited */
    public ?int $editingId = null;

    public string $name = '';
    public string $identity_number = '';
    public string $email = '';
    public string $phone = '';
    public string $street = '';
    public string $postal_code = '';
    public string $city = '';
    public string $notes = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        // Max lengths mirror the contacts table columns
        return [
            'name'            => ['required', 'string', 'max:255'],
            'identity_number' => ['nullable', 'string', 'max:32'],
            'email'           => ['nullable', 'email', 'max:48'],
            'phone'           => ['nullable', 'string', 'max:16'],
            'street'          => ['nullable', 'string', 'max:32'],
            'postal_code'     => ['nullable', 'string', 'max:16'],
            'city'            => ['nullable', 'string', 'max:32'],
            'notes'           => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name'            => 'nazwa',
            'identity_number' => 'NIP',
            'email'           => 'e-mail',
            'phone'           => 'telefon',
            'street'          => 'ulica',
            'postal_code'     => 'kod pocztowy',
            'city'            => 'miasto',
            'notes'           => 'notatki',
        ];
    }

    public function create(): void
    {
        $this->resetForm();

        Flux::modal('contact-form')->show();
    }

    public function edit(int $id): void
    {
        $contact = Contact::findOrFail($id);

        $this->resetValidation();
        $this->editingId = $contact->id;
        $this->fill($contact->only([
            'name', 'identity_number', 'email', 'phone',
            'street', 'postal_code', 'city',
        ]));
        // Nullable text column — keep the property a string
        $this->notes = (string) $contact->notes;

        Flux::modal('contact-form')->show();
    }

    public function save(): void
    {
        $data = $this->validate();

        // Empty optional fields are stored as empty strings (columns are NOT NULL in legacy data)
        $data = array_map(fn ($v) => $v ?? '', $data);

        Contact::updateOrCreate(['id' => $this->editingId], $data);

        Flux::modal('contact-form')->close();
        Flux::toast($this->editingId ? 'Kontakt zapisany.' : 'Kontakt dodany.');

        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'name', 'identity_number', 'email',
            'phone', 'street', 'postal_code', 'city', 'notes',
        ]);
        $this->resetValidation();
    }

    public function render()
    {
        $term = trim($this->search);

        $contacts = Contact::query()
            // Match name, tax id (NIP), email, phone or city
            ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('identity_number', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('city', 'like', "%{$term}%")))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($this->perPage);

        return view('livewire.contacts.index', [
            'contacts' => $contacts,
        ]);
    }
}