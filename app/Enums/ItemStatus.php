<?php

namespace App\Enums;

enum ItemStatus: int
{
    case Store     = 1;
    case Lost      = 2;
    case Destroyed = 3;
    case Sold      = 4;
    case Transfer  = 5;
    case Invalid   = 6;

    public function label(): string
    {
        return match ($this) {
            self::Store     => 'Na magazynie',
            self::Lost      => 'Zagubiony',
            self::Destroyed => 'Zniszczony',
            self::Sold      => 'Sprzedany',
            self::Transfer  => 'W transferze',
            self::Invalid   => 'Unieważniony',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Store     => 'green',
            self::Lost      => 'yellow',
            self::Destroyed => 'red',
            self::Sold      => 'blue',
            self::Transfer  => 'purple',
            self::Invalid   => 'zinc',
        };
    }
}