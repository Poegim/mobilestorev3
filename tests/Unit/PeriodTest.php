<?php

namespace Tests\Unit;

use App\Enums\Period;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PeriodTest extends TestCase
{
    protected function tearDown(): void
    {
        // Always unfreeze time so other tests are not affected
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[DataProvider('lastMonthCases')]
    public function test_last_month_on_edge_dates(string $now, string $from, string $to): void
    {
        Carbon::setTestNow($now);

        [$start, $end] = Period::LastMonth->dateRange();

        $this->assertSame($from, $start->toDateString());
        $this->assertSame($to, $end->toDateString());
    }

    public static function lastMonthCases(): array
    {
        return [
            '31 Mar -> Feb' => ['2026-03-31 12:00', '2026-02-01', '2026-02-28'],
            '31 Jul -> Jun' => ['2026-07-31 12:00', '2026-06-01', '2026-06-30'],
            '15 Jan -> Dec' => ['2026-01-15 12:00', '2025-12-01', '2025-12-31'],
        ];
    }
}