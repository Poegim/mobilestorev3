<?php

namespace App\Enums;

enum SellStatus: string
{
    case Completed = 'completed';  // valid + bill printed
    case NoBill    = 'no_bill';    // valid, no bill printed
    case Cancelled = 'cancelled';  // invalidated

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Paragon',
            self::NoBill    => 'Bez paragonu',
            self::Cancelled => 'Anulowana',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Completed => 'green',
            self::NoBill    => 'amber',
            self::Cancelled => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Completed => 'check-circle',
            self::NoBill    => 'minus-circle',
            self::Cancelled => 'x-circle',
        };
    }
}