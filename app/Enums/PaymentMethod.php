<?php

namespace App\Enums;

enum PaymentMethod: int
{
    case Cash     = 1;
    case Card     = 2;
    case Transfer = 3;
    case Allegro  = 4;

    public function label(): string
    {
        return match ($this) {
            self::Cash     => 'Gotówka',
            self::Card     => 'Karta',
            self::Transfer => 'Przelew',
            self::Allegro  => 'Allegro',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Cash     => 'banknotes',
            self::Card     => 'credit-card',
            self::Transfer => 'building-library',
            self::Allegro  => 'shopping-bag',
        };
    }
}