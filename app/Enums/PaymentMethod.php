<?php

namespace App\Enums;

enum PaymentMethod: int
{
    case Cash     = 1;
    case Transfer = 2;
    case COD      = 3;
    case Barter   = 4;

    public function label(): string
    {
        return match ($this) {
            self::Cash     => 'Gotówka',
            self::Transfer => 'Przelew',
            self::COD      => 'Pobranie',
            self::Barter   => 'Barter',
        };
    }
}