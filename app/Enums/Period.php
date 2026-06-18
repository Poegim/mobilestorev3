<?php

namespace App\Enums;

use Carbon\Carbon;

enum Period: string
{
    case Today     = 'today';
    case Yesterday = 'yesterday';
    case Week      = 'week';
    case Month     = 'month';
    case Quarter   = 'quarter';
    case Year      = 'year';
    case All       = 'all';

    public function label(): string
    {
        return match ($this) {
            self::Today     => 'Dzisiaj',
            self::Yesterday => 'Wczoraj',
            self::Week      => 'Ostatni tydzień',
            self::Month     => 'Ostatni miesiąc',
            self::Quarter   => 'Ostatni kwartał',
            self::Year      => 'Ostatni rok',
            self::All       => 'Wszystko',
        };
    }

    public function startDate(): ?Carbon
    {
        return match ($this) {
            self::Today     => Carbon::today(),
            self::Yesterday => Carbon::yesterday(),
            self::Week      => Carbon::now()->subWeek(),
            self::Month     => Carbon::now()->subMonth(),
            self::Quarter   => Carbon::now()->subQuarter(),
            self::Year      => Carbon::now()->subYear(),
            self::All       => null,
        };
    }
}