<?php

namespace App\Enums;

use Carbon\Carbon;

enum Period: string
{
    case Today      = 'today';
    case Yesterday  = 'yesterday';
    case ThisWeek   = 'this_week';
    case ThisMonth  = 'this_month';
    case LastMonth  = 'last_month';
    case ThisYear   = 'this_year';
    case All        = 'all';

    public function label(): string
    {
        return match ($this) {
            self::Today      => 'Dzisiaj',
            self::Yesterday  => 'Wczoraj',
            self::ThisWeek   => 'Ten tydzień',
            self::ThisMonth  => 'Ten miesiąc',
            self::LastMonth  => 'Ostatni miesiąc',
            self::ThisYear   => 'Ten rok',
            self::All        => 'Wszystko',
        };
    }

    public function dateRange(): ?array
    {
        return match ($this) {
            self::Today     => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            self::Yesterday => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()],
            self::ThisWeek  => [Carbon::now()->startOfWeek(), now()],
            self::ThisMonth => [Carbon::now()->startOfMonth(), now()],
            self::LastMonth => [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()],
            self::ThisYear  => [Carbon::now()->startOfYear(), now()],
            self::All       => null,
        };
    }
}