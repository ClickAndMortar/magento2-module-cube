<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Test\Unit;

use ClickAndMortar\Cube\Api\Data\DayTotalsInterfaceFactory;
use ClickAndMortar\Cube\Api\Data\EventsResultInterfaceFactory;
use ClickAndMortar\Cube\Api\Data\OrderEventInterfaceFactory;
use ClickAndMortar\Cube\Api\Data\OrderSummaryInterfaceFactory;
use ClickAndMortar\Cube\Api\Data\PeriodTotalsInterfaceFactory;
use ClickAndMortar\Cube\Api\Data\StatsResultInterfaceFactory;
use ClickAndMortar\Cube\Model\Clock;
use ClickAndMortar\Cube\Model\Config;
use ClickAndMortar\Cube\Model\Data;
use ClickAndMortar\Cube\Model\History;
use ClickAndMortar\Cube\Model\OrderSummaryMapper;
use ClickAndMortar\Cube\Model\ResourceModel\OrderStats;
use ClickAndMortar\Cube\Model\StatsCalculator;
use Magento\Framework\App\CacheInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

abstract class ServiceTestCase extends TestCase
{
    protected Config&Stub $config;
    protected Clock&Stub $clock;
    protected OrderStats&MockObject $orderStats;
    protected CacheInterface&MockObject $cache;
    protected History $history;
    protected OrderSummaryMapper $mapper;

    protected function setUp(): void
    {
        $this->config = $this->createStub(Config::class);
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getTimezone')->willReturn(new \DateTimeZone('Europe/Paris'));
        $this->config->method('getStoreIds')->willReturn([1]);
        $this->config->method('getExcludedStates')->willReturn(['canceled']);
        $this->config->method('getEventsLimit')->willReturn(20);
        $this->config->method('getRecordWindowDays')->willReturn(365);
        $this->config->method('getBaseCurrency')->willReturn('EUR');

        $this->clock = $this->createStub(Clock::class);
        // 16/11/2026 14:00 à Paris
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2026-11-16 13:00:00', new \DateTimeZone('UTC')));

        $this->orderStats = $this->createMock(OrderStats::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->history = new History($this->orderStats, new StatsCalculator(), $this->cache, $this->config);
        $this->mapper = new OrderSummaryMapper(
            $this->factory(OrderSummaryInterfaceFactory::class, Data\OrderSummary::class),
            $this->factory(OrderEventInterfaceFactory::class, Data\OrderEvent::class)
        );
    }

    /**
     * Fabrique simulée qui renvoie un nouvel objet de données à chaque appel.
     */
    protected function factory(string $factoryClass, string $dataClass): MockObject
    {
        $factory = $this->getMockBuilder($factoryClass)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $factory->method('create')->willReturnCallback(static fn () => new $dataClass());
        return $factory;
    }

    protected function statsFactories(): array
    {
        return [
            $this->factory(StatsResultInterfaceFactory::class, Data\StatsResult::class),
            $this->factory(PeriodTotalsInterfaceFactory::class, Data\PeriodTotals::class),
            $this->factory(DayTotalsInterfaceFactory::class, Data\DayTotals::class),
        ];
    }

    protected function eventsFactory(): MockObject
    {
        return $this->factory(EventsResultInterfaceFactory::class, Data\EventsResult::class);
    }
}
