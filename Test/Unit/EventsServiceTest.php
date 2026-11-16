<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Test\Unit;

use ClickAndMortar\Cube\Model\EventsService;
use ClickAndMortar\Cube\Model\PeriodResolver;
use ClickAndMortar\Cube\Model\StatsCalculator;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class EventsServiceTest extends ServiceTestCase
{
    private function service(): EventsService
    {
        return new EventsService(
            $this->config,
            $this->clock,
            new PeriodResolver(),
            $this->orderStats,
            $this->history,
            new StatsCalculator(),
            $this->mapper,
            $this->eventsFactory()
        );
    }

    private function row(int $id, float $amount, string $createdAt, string $city = 'Nantes'): array
    {
        return [
            'entity_id' => (string) $id, 'increment_id' => sprintf('%09d', $id), 'amount' => (string) $amount,
            'items' => '2.0000', 'created_at' => $createdAt, 'city' => $city,
        ];
    }

    public function testSinceZeroOnlyReturnsCursor(): void
    {
        $this->orderStats->method('maxEntityId')->willReturn(321);
        $this->orderStats->expects(self::never())->method('ordersSince');

        $result = $this->service()->getEvents(0);

        self::assertSame([], $result->getEvents());
        self::assertSame(321, $result->getLastEntityId());
        self::assertSame(0, $result->getSkipped());
    }

    public function testCursorIsResetWhenAheadOfDatabase(): void
    {
        $this->orderStats->method('maxEntityId')->willReturn(50);
        $this->orderStats->method('ordersSince')->willReturn([]);
        self::assertSame(50, $this->service()->getEvents(900)->getLastEntityId());
        self::assertSame(40, $this->service()->getEvents(40)->getLastEntityId());
    }

    public function testEventsWithFlagsAndLimit(): void
    {
        $this->orderStats->method('maxEntityId')->willReturn(103);
        $this->orderStats->expects(self::once())->method('ordersSince')
            ->with(99, 3, [1], ['canceled'])
            ->willReturn([
                $this->row(101, 40.0, '2026-11-15 22:30:00'), // yesterday 23:30 in Paris
                $this->row(102, 50.0, '2026-11-15 23:10:00'), // today 00:10
                $this->row(103, 70.0, '2026-11-16 12:00:00'),
            ]);
        $this->orderStats->method('countSince')->willReturn(4);
        $this->orderStats->method('minEntityIdBetween')->willReturn(102);
        $this->orderStats->method('revenueBefore')->with('2026-11-15 23:00:00', '2026-11-16 23:00:00', 102)->willReturn(0.0);
        $this->cache->method('load')->willReturn(json_encode(['2026-11-14' => ['revenue' => 100.0, 'orders' => 2]]));

        $result = $this->service()->getEvents(99, 3);

        $events = $result->getEvents();
        self::assertCount(3, $events);
        self::assertSame(103, $result->getLastEntityId());
        self::assertSame(1, $result->getSkipped());

        self::assertSame(101, $events[0]->getEntityId());
        self::assertFalse($events[0]->getFirstOfDay());
        self::assertFalse($events[0]->getRecordBroken());
        self::assertSame('2026-11-15T23:30:00+01:00', $events[0]->getCreatedAt());

        self::assertTrue($events[1]->getFirstOfDay());
        self::assertFalse($events[1]->getRecordBroken()); // 50 <= 100

        self::assertFalse($events[2]->getFirstOfDay());
        self::assertTrue($events[2]->getRecordBroken()); // 120 > 100
        self::assertSame(70.0, $events[2]->getAmount());
        self::assertSame(2, $events[2]->getItems());
        self::assertSame('Nantes', $events[2]->getCity());
    }

    public function testLimitIsCapped(): void
    {
        $this->orderStats->method('maxEntityId')->willReturn(5);
        $this->orderStats->expects(self::once())->method('ordersSince')->with(1, 100)->willReturn([]);
        $this->service()->getEvents(1, 5000);
    }
}
