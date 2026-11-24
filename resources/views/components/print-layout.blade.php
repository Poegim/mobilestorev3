@props(['html'])

<div x-data="{
    save() { $wire.save($refs.content.innerHTML) },
}">
    <div class="flex items-center gap-2 p-4 print:hidden">
        <flux:button x-on:click="save">Zapisz</flux:button>
        <flux:button wire:click="regenerate" wire:confirm="Odrzucić ręczne poprawki i wygenerować dokument od nowa?">
            Wygeneruj ponownie
        </flux:button>
        <flux:button variant="primary" x-on:click="window.print()">Drukuj</flux:button>
    </div>

    <style>
        .printable-document {
            background: #ffffff;
            color: #111111;
            font-family: Georgia, 'Times New Roman', serif;
            line-height: 1.5;
        }
        .printable-document h1 {
            font-size: 1.4em;
            font-weight: bold;
            text-align: center;
            margin: 0.6em 0;
        }
        .printable-document p {
            margin: 0.6em 0;
        }
        .printable-document ul,
        .printable-document ol {
            margin: 0.6em 0;
            padding-left: 1.5em;
            list-style: initial;
        }
        .printable-document li {
            display: list-item;
            margin: 0.3em 0;
        }
        .printable-document hr {
            margin: 1em 0;
            border: none;
            border-top: 1px solid #ccc;
        }
        .print-page-separator {
            height: 0;
            overflow: hidden;
        }

        @media screen {
            body {
                background: #e5e5e5;
            }
            .printable-document {
                width: 21cm;
                padding: 2cm;
                margin: 1.5rem auto;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            }
        }

        @media print {
            @page { margin: 0; }
            .printable-document {
                width: 100%;
                padding: 2cm;
                margin: 0;
                box-shadow: none;
            }
            .print-page-separator {
                page-break-after: always;
            }
        }
    </style>

    <div class="printable-document" x-ref="content" contenteditable="true">
        {!! $html !!}
    </div>

</div>