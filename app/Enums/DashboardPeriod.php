<?php

namespace App\Enums;

use Carbon\CarbonImmutable;

enum DashboardPeriod: string
{
    case Today       = 'today';
    case Yesterday   = 'yesterday';
    case Week        = 'week';
    case Month       = 'month';
    case Last30      = 'last30';
    case Last60      = 'last60';
    case Last90      = 'last90';
    case Quarter     = 'quarter';
    case LastQuarter = 'lastquarter';
    case Year        = 'year';
    case LastYear    = 'lastyear';

    public function label(): string
    {
        return match ($this) {
            self::Today       => 'Dziś',
            self::Yesterday   => 'Wczoraj',
            self::Week        => 'Ten tydzień',
            self::Month       => 'Ten miesiąc',
            self::Last30      => 'Ostatnie 30 dni',
            self::Last60      => 'Ostatnie 60 dni',
            self::Last90      => 'Ostatnie 90 dni',
            self::Quarter     => 'Ten kwartał',
            self::LastQuarter => 'Poprzedni kwartał',
            self::Year        => 'Ten rok',
            self::LastYear    => 'Poprzedni rok',
        };
    }

    /**
     * Inclusive [from, to] range for this period.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(): array
    {
        $now = CarbonImmutable::now();

        return match ($this) {
            self::Today       => [$now->startOfDay(),                   $now->endOfDay()],
            self::Yesterday   => [$now->subDay()->startOfDay(),         $now->subDay()->endOfDay()],
            self::Week        => [$now->startOfWeek(),                  $now->endOfWeek()],
            self::Month       => [$now->startOfMonth(),                 $now->endOfMonth()],
            self::Last30      => [$now->subDays(30)->startOfDay(),      $now->endOfDay()],
            self::Last60      => [$now->subDays(60)->startOfDay(),      $now->endOfDay()],
            self::Last90      => [$now->subDays(90)->startOfDay(),      $now->endOfDay()],
            self::Quarter     => [$now->startOfQuarter(),               $now->endOfQuarter()],
            self::LastQuarter => [$now->subQuarter()->startOfQuarter(), $now->subQuarter()->endOfQuarter()],
            self::Year        => [$now->startOfYear(),                  $now->endOfYear()],
            self::LastYear    => [$now->subYear()->startOfYear(),       $now->subYear()->endOfYear()],
        };
    }
}