{{-- resources/views/livewire/admin/users/show.blade.php --}}
<div>
    <flux:main container>
        <div class="flex items-center gap-4 mb-6">
            <flux:button
                href="{{ route('admin.users.index') }}"
                wire:navigate
                variant="subtle"
                icon="arrow-left"
            >
                Użytkownicy
            </flux:button>
            <flux:heading size="xl">{{ $user->name ?? $user->login }}</flux:heading>
        </div>

        <div class="max-w-lg space-y-6">
            <flux:field>
                <flux:label>Nazwa</flux:label>
                <flux:input wire:model="name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>E-mail</flux:label>
                <flux:input wire:model="email" type="email" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>
                    Nowe hasło
                    <span class="text-zinc-400 font-normal">(zostaw puste aby nie zmieniać)</span>
                </flux:label>
                <flux:input wire:model="newPassword" type="password" />
                <flux:error name="newPassword" />
            </flux:field>

            <flux:field>
                <flux:label>Potwierdź nowe hasło</flux:label>
                <flux:input wire:model="newPasswordConfirmation" type="password" />
                <flux:error name="newPasswordConfirmation" />
            </flux:field>

            <flux:field>
                <flux:label>Rola</flux:label>
                <flux:select wire:model.live="privilege">
                    @foreach($this->privilegeCases as $case)
                        <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="privilege" />
            </flux:field>

            @if((int) $privilege < \App\Enums\UserPrivilege::Admin->value)
                <flux:field>
                    <flux:label>Dostęp do sklepów</flux:label>
                    <div class="space-y-2 mt-1">
                        @foreach($this->allShops as $shop)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <flux:checkbox wire:model="selectedShops" value="{{ $shop->id }}" />
                                <span>{{ $shop->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </flux:field>
            @else
                <flux:callout icon="information-circle" color="blue">
                    Administrator ma dostęp do wszystkich sklepów.
                </flux:callout>
            @endif

            <div class="flex justify-end">
                <flux:button variant="primary" wire:click="save">Zapisz</flux:button>
            </div>
        </div>
    </flux:main>
</div>