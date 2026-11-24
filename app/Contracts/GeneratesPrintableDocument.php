<?php

namespace App\Contracts;

use App\Enums\PrintableDocumentType;

interface GeneratesPrintableDocument
{
    public function printableDocumentView(PrintableDocumentType $type): string;

    public function printableDocumentData(PrintableDocumentType $type): array;
}