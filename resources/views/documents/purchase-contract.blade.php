{{-- Content-only template: rendered once by PrintableDocumentService, then stored as editable HTML. --}}
@php
    $contact = $purchase->contact;
    $shop = $purchase->shop;
@endphp

@foreach ($purchase->purchasedItems as $i => $purchasedItem)
    @php $item = $purchasedItem->item; @endphp

    <div style="font-size: 0.85em;">
        <p>Miejscowość: {{ $shop->address_city }}<br>
        Data: {{ $purchase->created_at->format('Y-m-d') }}</p>

        <header style="text-align: center;">
            <h1>{{ $purchase->contractReference() }}@if($purchase->purchasedItems->count() > 1)/{{ $i + 1 }}@endif</h1>
        </header>

        <p>
            Dane kontrahenta: {{ $contact?->name ?? '—' }},
            identyfikator: {{ $contact?->identity_number ?? '—' }}<br>
            Adres: {{ $contact?->street }}, {{ $contact?->postal_code }} {{ $contact?->city }}
        </p>

        <p>do kupna-sprzedaży:</p>
        <ul>
            <li>
                {{ $item?->product?->brand?->name }} {{ $item?->product?->name }}
                @if($item?->feature_imei)
                    , IMEI: {{ $item->feature_imei }}
                @endif
                <br>Ilość: 1, należność: {{ number_format($purchasedItem->price / 100, 2) }} zł
            </li>
        </ul>

        <hr>

        <p>Oświadczam, że wyżej wymieniony przedmiot stanowi moją własność, nie jest zajęty przez komornika sądowego ani przez Urząd Skarbowy, oraz że nie pochodzi z przestępstwa.</p>

        <p><br>....................................<br>Podpis kontrahenta</p>
    </div>

    @if (! $loop->last)
        <div class="print-page-separator"></div>
    @endif
@endforeach