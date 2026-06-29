{{-- resources/views/livewire/admin/users/index.blade.php --}}
<div>
    <flux:main container>
        <div class="flex items-center justify-between mb-6">
            <flux:heading size="xl">Użytkownicy</flux:heading>
            <flux:button href="{{ route('admin.users.create') }}" wire:navigate variant="primary" icon="plus">
                Nowy użytkownik
            </flux:button>
        </div>

        {{-- Filters --}}
        <div class="flex flex-wrap gap-3 mb-4">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Szukaj po nazwie lub e-mail…"
                icon="magnifying-glass"
                class="w-64"
            />
            <flux:select wire:model.live="privilegeFilter" class="w-48">
                <flux:select.option value="">Wszystkie role</flux:select.option>
                @foreach($this->privilegeCases as $case)
                    <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        {{-- Table --}}
        <flux:table :paginate="$users">
            <flux:table.columns>
                <flux:table.column>Nazwa</flux:table.column>
                <flux:table.column>E-mail</flux:table.column>
                <flux:table.column>Rola</flux:table.column>
                <flux:table.column>Sklepy</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($users as $user)
                    <flux:table.row wire:key="user-{{ $user->id }}">
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:avatar size="sm">{{ $user->initials() }}</flux:avatar>
                                <span class="font-medium">{{ $user->name }}</span>
                                @if($user->id === auth()->id())
                                    <flux:badge size="sm" color="blue">Ty</flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $user->email ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @php $priv = $user->privilege; @endphp
                            <flux:badge size="sm" color="{{ match(true) {
                                $priv->value === 0 => 'red',
                                $priv->value >= 4  => 'green',
                                default            => 'zinc'
                            } }}">
                                {{ $priv->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500">
                            @if($user->privilege->isAdmin())
                                <span class="italic">wszystkie</span>
                            @else
                                @forelse($user->shops as $shop)
                                    <div>{{ $shop->name }}</div>
                                @empty
                                    —
                                @endforelse
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <div class="flex justify-end gap-2">
                                <flux:button
                                    size="sm"
                                    href="{{ route('admin.users.show', $user) }}"
                                    wire:navigate
                                    icon="pencil"
                                >
                                    Edytuj
                                </flux:button>
                                @if($user->id !== auth()->id())
                                    <flux:button
                                        size="sm"
                                        variant="danger"
                                        wire:click="deleteUser({{ $user->id }})"
                                        wire:confirm="Czy na pewno chcesz usunąć użytkownika {{ $user->name }}?"
                                        icon="trash"
                                    >
                                        Usuń
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-zinc-400 py-8">
                            Brak użytkowników.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:main>

</div>