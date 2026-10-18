<?php

namespace Tests\Unit;

use App\Enums\DashboardPeriod;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DashboardPeriodTest extends TestCase
{
    protected function tearDown(): void
    {
        // Always unfreeze time so other tests are not affected
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    #[DataProvider('edgeCases')]
    public function test_range_on_edge_case_dates(string $now, DashboardPeriod $period, string $from, string $to): void
    {
        // The enum uses CarbonImmutable, so freeze both flavours
        Carbon::setTestNow($now);
        CarbonImmutable::setTestNow($now);

        [$start, $end] = $period->range();

        $this->assertSame($from, $start->toDateString());
        $this->assertSame($to, $end->toDateString());
    }

    public static function edgeCases(): array
    {
        return [
            'last quarter on 31 Dec' => ['2026-12-31 12:00', DashboardPeriod::LastQuarter, '2026-07-01', '2026-09-30'],
            'last month on 31 Mar'   => ['2026-03-31 12:00', DashboardPeriod::LastMonth,   '2026-02-01', '2026-02-28'],
            'last month in January'  => ['2026-01-15 12:00', DashboardPeriod::LastMonth,   '2025-12-01', '2025-12-31'],
            'last 30 days'           => ['2026-12-31 12:00', DashboardPeriod::Last30,      '2026-12-02', '2026-12-31'],
            'last 90 days'           => ['2026-12-31 12:00', DashboardPeriod::Last90,      '2026-10-03', '2026-12-31'],
        ];
    }
}