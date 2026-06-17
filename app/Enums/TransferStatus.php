<?php

namespace App\Enums;

enum TransferStatus: int
{
    case Active    = 1;
    case Canceled  = 2;
    case Completed = 3;
    case Lost      = 4;

    public function label(): string
    {
        return match ($this) {
            self::Active    => 'W trakcie',
            self::Canceled  => 'Anulowany',
            self::Completed => 'Zakończony',
            self::Lost      => 'Zgubiony',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active    => 'yellow',
            self::Canceled  => 'zinc',
            self::Completed => 'green',
            self::Lost      => 'red',
        };
    }
}