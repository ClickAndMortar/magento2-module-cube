<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Test\Unit;

use ClickAndMortar\Cube\Model\PeriodResolver;
use ClickAndMortar\Cube\Model\StatsCalculator;
use ClickAndMortar\Cube\Model\StatsService;
use Magento\Framework\Webapi\Exception as WebapiException;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class StatsServiceTest extends ServiceTestCase
{
    private function service(): StatsService
    {
        [$results, $totals, $days] = $this->statsFactories();
        return new StatsService(
            $this->config,
            $this->clock,
            new PeriodResolver(),
            $this->orderStats,
            $this->history,
            new StatsCalculator(),
            $this->mapper,
            $results,
            $totals,
            $days
        );
    }

    public function testStats(): void
    {
        $this->config->method('getScreens')->willReturn(['today', 'week', 'goal']);
        $this->config->method('getDailyGoal')->willReturn(2000.0);

        $this->orderStats->method('totals')->willReturnMap([
            ['2026-11-15 23:00:00', '2026-11-16 23:00:00', [1], ['canceled'], ['revenue' => 900.0, 'orders' => 9]],
            ['2026-11-14 23:00:00', '2026-11-15 13:00:00', [1], ['canceled'], ['revenue' => 600.0, 'orders' => 10]],
        ]);
        $this->orderStats->expects(self::once())->method('quarterHourBuckets')
            ->with('2025-11-15 23:00:00', '2026-11-15 23:00:00', [1], ['canceled'])
            ->willReturn([
                ['bucket' => '2026-11-10 10:00', 'revenue' => '1200.00', 'orders' => '12'],
                ['bucket' => '2026-11-15 12:30', 'revenue' => '700.00', 'orders' => '7'],
                ['bucket' => '2026-11-15 22:45', 'revenue' => '100.00', 'orders' => '1'], // 23:45 local time, still the 15th
            ]);
        $this->orderStats->method('lastOrder')->willReturn([
            'entity_id' => '1234', 'increment_id' => '000001234', 'amount' => '89.9000',
            'items' => '3.0000', 'created_at' => '2026-11-16 12:58:00', 'city' => 'Lyon',
        ]);
        $this->cache->expects(self::once())->method('save');

        $result = $this->service()->getStats();

        self::assertSame('2026-11-16T14:00:00+01:00', $result->getGeneratedAt());
        self::assertSame('Europe/Paris', $result->getTimezone());
        self::assertSame('EUR', $result->getCurrency());
        self::assertSame([1], $result->getStoreIds());
        self::assertSame(['today', 'week', 'goal'], $result->getScreens());
        self::assertSame(900.0, $result->getToday()->getRevenue());
        self::assertSame(9, $result->getToday()->getOrders());
        self::assertSame(100.0, $result->getToday()->getAverageOrder());
        self::assertSame(600.0, $result->getYesterdaySameTime()->getRevenue());
        self::assertSame(50.0, $result->getRevenueDeltaPct());
        self::assertSame(-10.0, $result->getOrdersDeltaPct());
        self::assertSame(2000.0, $result->getGoalRevenue());
        self::assertSame(45.0, $result->getGoalProgressPct());
        self::assertSame(1200.0, $result->getRecordRevenue());
        self::assertSame('2026-11-10', $result->getRecordDate());
        self::assertFalse($result->getRecordBrokenToday());

        $days = $result->getWeek();
        self::assertCount(7, $days);
        self::assertSame('2026-11-10', $days[0]->getDate());
        self::assertSame(1200.0, $days[0]->getRevenue());
        self::assertSame('2026-11-15', $days[5]->getDate());
        self::assertSame(800.0, $days[5]->getRevenue());
        self::assertSame(8, $days[5]->getOrders());
        self::assertSame('2026-11-16', $days[6]->getDate());
        self::assertSame(900.0, $days[6]->getRevenue());

        $last = $result->getLastOrder();
        self::assertSame(1234, $last->getEntityId());
        self::assertSame(89.9, $last->getAmount());
        self::assertSame(3, $last->getItems());
        self::assertSame('Lyon', $last->getCity());
        self::assertSame('2026-11-16T13:58:00+01:00', $last->getCreatedAt());
        self::assertSame(1234, $result->getLastEntityId());
    }

    public function testEmptyShopAndCachedHistory(): void
    {
        $this->config->method('getScreens')->willReturn(['today']);
        $this->config->method('getDailyGoal')->willReturn(0.0);
        $this->orderStats->method('totals')->willReturn(['revenue' => 0.0, 'orders' => 0]);
        $this->orderStats->method('lastOrder')->willReturn(null);
        $this->cache->method('load')->willReturn('[]');
        $this->orderStats->expects(self::never())->method('quarterHourBuckets');

        $result = $this->service()->getStats();

        self::assertNull($result->getRevenueDeltaPct());
        self::assertNull($result->getGoalRevenue());
        self::assertNull($result->getGoalProgressPct());
        self::assertNull($result->getRecordRevenue());
        self::assertNull($result->getLastOrder());
        self::assertSame(0, $result->getLastEntityId());
        self::assertSame(0.0, $result->getToday()->getAverageOrder());
    }

    public function testDisabled(): void
    {
        $config = $this->createStub(\ClickAndMortar\Cube\Model\Config::class);
        $config->method('isEnabled')->willReturn(false);
        $this->config = $config;
        $this->expectException(WebapiException::class);
        try {
            $this->service()->getStats();
        } catch (WebapiException $e) {
            self::assertSame(403, $e->getHttpCode());
            throw $e;
        }
    }
}
