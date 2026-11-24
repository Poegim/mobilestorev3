<?php

namespace App\Enums;

enum PrintableDocumentType: string
{
    case Contract = 'contract'; // purchase agreement (umowa kupna-sprzedaży)

    public function label(): string
    {
        return match ($this) {
            self::Contract => 'Umowa kupna-sprzedaży',
        };
    }
}