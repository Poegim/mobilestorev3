<?php

namespace App\Enums;

enum PurchaseType: int
{
    case Invoice = 1;
    case Agreement = 2;

    public function label(): string
    {
        return match ($this) {
            self::Invoice => 'Faktura VAT 23%',
            self::Agreement => 'Umowa kupna-sprzedaży',
        };
    }
}