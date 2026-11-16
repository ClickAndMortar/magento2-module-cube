<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Test\Unit;

use ClickAndMortar\Cube\Model\StatsCalculator;
use PHPUnit\Framework\TestCase;

class StatsCalculatorTest extends TestCase
{
    private StatsCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new StatsCalculator();
    }

    public function testAggregateDaysUsesLocalMidnight(): void
    {
        $days = $this->calculator->aggregateDays([
            ['bucket' => '2026-11-15 22:45', 'revenue' => '10.00', 'orders' => '1'], // 23:45 in Paris on the 15th
            ['bucket' => '2026-11-15 23:00', 'revenue' => '20.50', 'orders' => '2'], // 00:00 in Paris on the 16th
            ['bucket' => '2026-11-16 12:15', 'revenue' => '5.25', 'orders' => '1'],
        ], new \DateTimeZone('Europe/Paris'));

        self::assertSame([
            '2026-11-15' => ['revenue' => 10.0, 'orders' => 1],
            '2026-11-16' => ['revenue' => 25.75, 'orders' => 3],
        ], $days);
    }

    public function testAggregateDaysAcrossDstChange(): void
    {
        // On 2026-10-25 Paris goes from UTC+2 to UTC+1: the local day runs from 22:00 UTC (10/24) to 23:00 UTC (10/25).
        $days = $this->calculator->aggregateDays([
            ['bucket' => '2026-10-24 21:45', 'revenue' => '1', 'orders' => '1'],
            ['bucket' => '2026-10-24 22:00', 'revenue' => '2', 'orders' => '1'],
            ['bucket' => '2026-10-25 22:45', 'revenue' => '4', 'orders' => '1'],
            ['bucket' => '2026-10-25 23:00', 'revenue' => '8', 'orders' => '1'],
        ], new \DateTimeZone('Europe/Paris'));

        self::assertSame(1.0, $days['2026-10-24']['revenue']);
        self::assertSame(6.0, $days['2026-10-25']['revenue']);
        self::assertSame(2, $days['2026-10-25']['orders']);
        self::assertSame(8.0, $days['2026-10-26']['revenue']);
    }

    public function testDeltaAverageGoal(): void
    {
        self::assertSame(25.0, $this->calculator->deltaPct(125.0, 100.0));
        self::assertSame(-33.3, $this->calculator->deltaPct(2.0, 3.0));
        self::assertNull($this->calculator->deltaPct(10.0, 0.0));
        self::assertSame(33.33, $this->calculator->average(100.0, 3));
        self::assertSame(0.0, $this->calculator->average(0.0, 0));
        self::assertSame(150.0, $this->calculator->goalProgress(1500.0, 1000.0));
        self::assertNull($this->calculator->goalProgress(1500.0, 0.0));
    }

    public function testRecordPicksMostRecentBestDay(): void
    {
        self::assertNull($this->calculator->record([]));
        self::assertSame(
            ['date' => '2026-11-12', 'revenue' => 300.0],
            $this->calculator->record([
                '2026-11-10' => ['revenue' => 300.0, 'orders' => 3],
                '2026-11-11' => ['revenue' => 120.0, 'orders' => 1],
                '2026-11-12' => ['revenue' => 300.0, 'orders' => 2],
            ])
        );
    }

    public function testEventFlags(): void
    {
        $orders = [
            ['entity_id' => 40, 'amount' => 80.0, 'is_today' => false], // yesterday
            ['entity_id' => 41, 'amount' => 50.0, 'is_today' => true],  // first of the day
            ['entity_id' => 42, 'amount' => 60.0, 'is_today' => true],
            ['entity_id' => 43, 'amount' => 70.0, 'is_today' => true],  // 180 > record 150
            ['entity_id' => 44, 'amount' => 10.0, 'is_today' => true],  // record already broken
        ];
        $flags = $this->calculator->eventFlags($orders, 41, 0.0, 150.0);

        self::assertSame(['first_of_day' => false, 'record_broken' => false], $flags[40]);
        self::assertSame(['first_of_day' => true, 'record_broken' => false], $flags[41]);
        self::assertSame(['first_of_day' => false, 'record_broken' => false], $flags[42]);
        self::assertSame(['first_of_day' => false, 'record_broken' => true], $flags[43]);
        self::assertSame(['first_of_day' => false, 'record_broken' => false], $flags[44]);
    }

    public function testEventFlagsWithPriorRevenueAndNoRecord(): void
    {
        $orders = [['entity_id' => 9, 'amount' => 30.0, 'is_today' => true]];
        self::assertTrue($this->calculator->eventFlags($orders, 9, 0.0, null)[9]['first_of_day']);
        self::assertFalse($this->calculator->eventFlags($orders, 9, 0.0, null)[9]['record_broken']);
        // 100 already made before order 9, record 120: 100 + 30 = 130 > 120
        self::assertTrue($this->calculator->eventFlags($orders, 5, 100.0, 120.0)[9]['record_broken']);
        self::assertFalse($this->calculator->eventFlags($orders, 5, 100.0, 120.0)[9]['first_of_day']);
        // Exactly equal to the record: not broken
        self::assertFalse($this->calculator->eventFlags($orders, 5, 90.0, 120.0)[9]['record_broken']);
    }
}
