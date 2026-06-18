<div>
    <div class="mb-6">
        <flux:heading size="xl">
            Dashboard {{ $shop ? '— ' . $shop->name : '' }}
        </flux:heading>
    </div>

    {{-- Main stats --}}
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-8">
        <flux:card>
            <flux:subheading>Na magazynie</flux:subheading>
            <div class="mt-2 text-3xl font-bold">{{ number_format($itemsInStock, 0, ',', ' ') }}</div>
            <flux:text class="mt-1">przedmiotów</flux:text>
        </flux:card>

        <flux:card>
            <flux:subheading>Sprzedaż dziś</flux:subheading>
            <div class="mt-2 text-3xl font-bold">{{ number_format($todayRevenue / 100, 2, ',', ' ') }} zł</div>
            <flux:text class="mt-1">{{ $todaySells }} {{ $todaySells === 1 ? 'transakcja' : 'transakcji' }}</flux:text>
        </flux:card>

        <flux:card>
            <flux:subheading>Sprzedaż ten miesiąc</flux:subheading>
            <div class="mt-2 text-3xl font-bold">{{ number_format($monthRevenue / 100, 2, ',', ' ') }} zł</div>
            <flux:text class="mt-1">{{ $monthSells }} transakcji</flux:text>
        </flux:card>

        <flux:card>
            <flux:subheading>Zakupy dziś</flux:subheading>
            <div class="mt-2 text-3xl font-bold">{{ $todayPurchases }}</div>
            <flux:text class="mt-1">przyjęć towaru</flux:text>
        </flux:card>
    </div>

    {{-- Today category stats --}}
    <div class="mb-8">
        @if($showTodayStats)
            <flux:heading size="lg" class="mb-4">Dziś — podział na kategorie</flux:heading>
            <div class="grid gap-4 md:grid-cols-3 {{ $isAdmin ? 'lg:grid-cols-4' : '' }}">
                <flux:card>
                    <flux:subheading>Urządzenia</flux:subheading>
                    <div class="mt-2 text-2xl font-bold">{{ number_format($todayCategories['devices']['revenue'] / 100, 2, ',', ' ') }} zł</div>
                    <flux:text class="mt-1">{{ $todayCategories['devices']['count'] }} szt.</flux:text>
                    @if($isAdmin && isset($todayCategories['devices']['profit']))
                        <div class="mt-2 text-sm {{ $todayCategories['devices']['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            Zysk: {{ number_format($todayCategories['devices']['profit'] / 100, 2, ',', ' ') }} zł
                        </div>
                    @endif
                </flux:card>

                <flux:card>
                    <flux:subheading>Akcesoria</flux:subheading>
                    <div class="mt-2 text-2xl font-bold">{{ number_format($todayCategories['accessories']['revenue'] / 100, 2, ',', ' ') }} zł</div>
                    <flux:text class="mt-1">{{ $todayCategories['accessories']['count'] }} szt.</flux:text>
                    @if($isAdmin && isset($todayCategories['accessories']['profit']))
                        <div class="mt-2 text-sm {{ $todayCategories['accessories']['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            Zysk: {{ number_format($todayCategories['accessories']['profit'] / 100, 2, ',', ' ') }} zł
                        </div>
                    @endif
                </flux:card>

                <flux:card>
                    <flux:subheading>Usługi</flux:subheading>
                    <div class="mt-2 text-2xl font-bold">{{ number_format($todayCategories['services']['revenue'] / 100, 2, ',', ' ') }} zł</div>
                    <flux:text class="mt-1">{{ $todayCategories['services']['count'] }} szt.</flux:text>
                    @if($isAdmin && isset($todayCategories['services']['profit']))
                        <div class="mt-2 text-sm {{ $todayCategories['services']['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            Zysk: {{ number_format($todayCategories['services']['profit'] / 100, 2, ',', ' ') }} zł
                        </div>
                    @endif
                </flux:card>

                @if($isAdmin && isset($todayCategories['totalProfit']))
                    <flux:card class="border-2 border-green-200 dark:border-green-800">
                        <flux:subheading>Zysk łączny dziś</flux:subheading>
                        <div class="mt-2 text-2xl font-bold {{ $todayCategories['totalProfit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($todayCategories['totalProfit'] / 100, 2, ',', ' ') }} zł
                        </div>
                    </flux:card>
                @endif
            </div>
        @else
            <flux:button wire:click="loadTodayStats" variant="subtle" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="loadTodayStats">Pokaż statystyki dnia</span>
                <span wire:loading wire:target="loadTodayStats">Ładowanie...</span>
            </flux:button>
        @endif
    </div>

    {{-- Month category stats --}}
    <div class="mb-8">
        @if($showMonthStats)
            <flux:heading size="lg" class="mb-4">Ten miesiąc — podział na kategorie</flux:heading>
            <div class="grid gap-4 md:grid-cols-3 {{ $isAdmin ? 'lg:grid-cols-4' : '' }}">
                <flux:card>
                    <flux:subheading>Urządzenia</flux:subheading>
                    <div class="mt-2 text-2xl font-bold">{{ number_format($monthCategories['devices']['revenue'] / 100, 2, ',', ' ') }} zł</div>
                    <flux:text class="mt-1">{{ $monthCategories['devices']['count'] }} szt.</flux:text>
                    @if($isAdmin && isset($monthCategories['devices']['profit']))
                        <div class="mt-2 text-sm {{ $monthCategories['devices']['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            Zysk: {{ number_format($monthCategories['devices']['profit'] / 100, 2, ',', ' ') }} zł
                        </div>
                    @endif
                </flux:card>

                <flux:card>
                    <flux:subheading>Akcesoria</flux:subheading>
                    <div class="mt-2 text-2xl font-bold">{{ number_format($monthCategories['accessories']['revenue'] / 100, 2, ',', ' ') }} zł</div>
                    <flux:text class="mt-1">{{ $monthCategories['accessories']['count'] }} szt.</flux:text>
                    @if($isAdmin && isset($monthCategories['accessories']['profit']))
                        <div class="mt-2 text-sm {{ $monthCategories['accessories']['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            Zysk: {{ number_format($monthCategories['accessories']['profit'] / 100, 2, ',', ' ') }} zł
                        </div>
                    @endif
                </flux:card>

                <flux:card>
                    <flux:subheading>Usługi</flux:subheading>
                    <div class="mt-2 text-2xl font-bold">{{ number_format($monthCategories['services']['revenue'] / 100, 2, ',', ' ') }} zł</div>
                    <flux:text class="mt-1">{{ $monthCategories['services']['count'] }} szt.</flux:text>
                    @if($isAdmin && isset($monthCategories['services']['profit']))
                        <div class="mt-2 text-sm {{ $monthCategories['services']['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            Zysk: {{ number_format($monthCategories['services']['profit'] / 100, 2, ',', ' ') }} zł
                        </div>
                    @endif
                </flux:card>

                @if($isAdmin && isset($monthCategories['totalProfit']))
                    <flux:card class="border-2 border-green-200 dark:border-green-800">
                        <flux:subheading>Zysk łączny miesiąc</flux:subheading>
                        <div class="mt-2 text-2xl font-bold {{ $monthCategories['totalProfit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($monthCategories['totalProfit'] / 100, 2, ',', ' ') }} zł
                        </div>
                    </flux:card>
                @endif
            </div>
        @else
            <flux:button wire:click="loadMonthStats" variant="subtle" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="loadMonthStats">Pokaż statystyki miesiąca</span>
                <span wire:loading wire:target="loadMonthStats">Ładowanie...</span>
            </flux:button>
        @endif
    </div>

    {{-- Recent tables --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <flux:card>
            <flux:heading size="lg" class="mb-4">Ostatnie sprzedaże</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>ID</flux:table.column>
                    <flux:table.column>Data</flux:table.column>
                    @if(!$shop)
                        <flux:table.column>Sklep</flux:table.column>
                    @endif
                    <flux:table.column>Pozycje</flux:table.column>
                    <flux:table.column>Kwota</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($recentSells as $sell)
                        <flux:table.row>
                            <flux:table.cell>#{{ $sell->id }}</flux:table.cell>
                            <flux:table.cell>{{ $sell->created_at->format('d.m.Y H:i') }}</flux:table.cell>
                            @if(!$shop)
                                <flux:table.cell>{{ $sell->shop->name ?? '-' }}</flux:table.cell>
                            @endif
                            <flux:table.cell>{{ $sell->soldItems->count() }}</flux:table.cell>
                            <flux:table.cell class="font-medium">
                                {{ number_format($sell->soldItems->sum('price') / 100, 2, ',', ' ') }} zł
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="{{ $shop ? 4 : 5 }}" class="text-center text-zinc-500">
                                Brak sprzedaży
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-4">Ostatnie zakupy</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>ID</flux:table.column>
                    <flux:table.column>Data</flux:table.column>
                    @if(!$shop)
                        <flux:table.column>Sklep</flux:table.column>
                    @endif
                    <flux:table.column>Dostawca</flux:table.column>
                    <flux:table.column>Pozycje</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($recentPurchases as $purchase)
                        <flux:table.row>
                            <flux:table.cell>#{{ $purchase->id }}</flux:table.cell>
                            <flux:table.cell>{{ $purchase->created_at->format('d.m.Y H:i') }}</flux:table.cell>
                            @if(!$shop)
                                <flux:table.cell>{{ $purchase->shop->name ?? '-' }}</flux:table.cell>
                            @endif
                            <flux:table.cell>{{ Str::limit($purchase->contact->name ?? '-', 20) }}</flux:table.cell>
                            <flux:table.cell>{{ $purchase->purchasedItems->count() }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="{{ $shop ? 4 : 5 }}" class="text-center text-zinc-500">
                                Brak zakupów
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</div>