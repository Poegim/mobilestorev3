<?php

namespace App\Enums;

enum PurchaseStatus: string
{
    case Draft     = 'draft';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Wersja robocza',
            self::Completed => 'Zakończony',
            self::Cancelled => 'Anulowany',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft     => 'amber',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }
}