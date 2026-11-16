<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Test\Unit;

use ClickAndMortar\Cube\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private function config(array $values): Config
    {
        $scope = $this->createStub(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturnCallback(static fn ($path) => $values[$path] ?? null);
        $scope->method('isSetFlag')->willReturnCallback(static fn ($path) => (bool) ($values[$path] ?? false));
        return new Config($scope);
    }

    public function testDefaults(): void
    {
        $config = $this->config([]);
        self::assertFalse($config->isEnabled());
        self::assertSame('Europe/Paris', $config->getTimezone()->getName());
        self::assertSame([], $config->getStoreIds());
        self::assertSame([], $config->getExcludedStates());
        self::assertSame(20, $config->getEventsLimit());
        self::assertSame(365, $config->getRecordWindowDays());
        self::assertSame('EUR', $config->getBaseCurrency());
    }

    public function testParsing(): void
    {
        $config = $this->config([
            Config::XML_ENABLED => '1',
            Config::XML_SCREENS => 'goal,week,today,unknown',
            Config::XML_DAILY_GOAL => '1500,50',
            Config::XML_TIMEZONE => 'America/Montreal',
            Config::XML_STORE_IDS => '3,1,,3,0',
            Config::XML_EXCLUDED_STATES => 'pending_payment,canceled',
            Config::XML_EVENTS_LIMIT => '500',
            Config::XML_RECORD_WINDOW => '2',
        ]);
        self::assertTrue($config->isEnabled());
        self::assertSame(['today', 'week', 'goal'], $config->getScreens());
        self::assertSame(1500.5, $config->getDailyGoal());
        self::assertSame('America/Montreal', $config->getTimezone()->getName());
        self::assertSame([1, 3], $config->getStoreIds());
        self::assertSame(['canceled', 'pending_payment'], $config->getExcludedStates());
        self::assertSame(100, $config->getEventsLimit());
        self::assertSame(7, $config->getRecordWindowDays());
    }

    public function testGoalScreenHiddenWithoutGoalAndBadTimezoneFallsBack(): void
    {
        $config = $this->config([
            Config::XML_SCREENS => 'today,goal',
            Config::XML_DAILY_GOAL => '0',
            Config::XML_TIMEZONE => 'Mars/Olympus',
        ]);
        self::assertSame(['today'], $config->getScreens());
        self::assertSame('Europe/Paris', $config->getTimezone()->getName());
    }
}
