<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model;

use ClickAndMortar\Cube\Api\Data\DayTotalsInterfaceFactory;
use ClickAndMortar\Cube\Api\Data\PeriodTotalsInterface;
use ClickAndMortar\Cube\Api\Data\PeriodTotalsInterfaceFactory;
use ClickAndMortar\Cube\Api\Data\StatsResultInterface;
use ClickAndMortar\Cube\Api\Data\StatsResultInterfaceFactory;
use ClickAndMortar\Cube\Api\StatsInterface;
use ClickAndMortar\Cube\Model\ResourceModel\OrderStats;
use Magento\Framework\Webapi\Exception as WebapiException;

class StatsService implements StatsInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly Clock $clock,
        private readonly PeriodResolver $periodResolver,
        private readonly OrderStats $orderStats,
        private readonly History $history,
        private readonly StatsCalculator $calculator,
        private readonly OrderSummaryMapper $mapper,
        private readonly StatsResultInterfaceFactory $resultFactory,
        private readonly PeriodTotalsInterfaceFactory $totalsFactory,
        private readonly DayTotalsInterfaceFactory $dayFactory
    ) {
    }

    public function getStats()
    {
        if (!$this->config->isEnabled()) {
            throw new WebapiException(__("L'API du cube est désactivée."), 0, WebapiException::HTTP_FORBIDDEN);
        }

        $periods = $this->periodResolver->resolve($this->config->getTimezone(), $this->clock->now());
        $storeIds = $this->config->getStoreIds();
        $states = $this->config->getExcludedStates();

        $today = $this->orderStats->totals($periods->todayStartUtc, $periods->tomorrowStartUtc, $storeIds, $states);
        $yesterday = $this->orderStats->totals(
            $periods->yesterdayStartUtc,
            $periods->yesterdaySameTimeUtc,
            $storeIds,
            $states
        );
        $days = $this->history->days($periods);
        $record = $this->calculator->record($days);
        $goal = $this->config->getDailyGoal();
        $lastOrderRow = $this->orderStats->lastOrder($storeIds, $states);

        $last7 = [];
        for ($offset = -6; $offset <= 0; $offset++) {
            $date = $periods->shiftDate($offset);
            $values = $offset === 0 ? $today : ($days[$date] ?? ['revenue' => 0.0, 'orders' => 0]);
            $last7[] = $this->dayFactory->create()
                ->setDate($date)
                ->setRevenue($values['revenue'])
                ->setOrders($values['orders']);
        }

        /** @var StatsResultInterface $result */
        $result = $this->resultFactory->create();
        $result->setGeneratedAt($periods->nowLocal->format(\DateTimeInterface::ATOM))
            ->setTimezone($periods->timezone->getName())
            ->setCurrency($this->config->getBaseCurrency())
            ->setStoreIds($storeIds)
            ->setScreens($this->config->getScreens())
            ->setToday($this->totals($today))
            ->setYesterdaySameTime($this->totals($yesterday))
            ->setRevenueDeltaPct($this->calculator->deltaPct($today['revenue'], $yesterday['revenue']))
            ->setOrdersDeltaPct($this->calculator->deltaPct((float) $today['orders'], (float) $yesterday['orders']))
            ->setGoalRevenue($goal > 0 ? $goal : null)
            ->setGoalProgressPct($this->calculator->goalProgress($today['revenue'], $goal))
            ->setRecordRevenue($record['revenue'] ?? null)
            ->setRecordDate($record['date'] ?? null)
            ->setRecordBrokenToday($record !== null && $today['revenue'] > $record['revenue'])
            ->setWeek($last7)
            ->setLastOrder($lastOrderRow ? $this->mapper->summary($lastOrderRow, $periods) : null)
            ->setLastEntityId($lastOrderRow ? (int) $lastOrderRow['entity_id'] : 0);
        return $result;
    }

    /**
     * @param array{revenue: float, orders: int} $values
     */
    private function totals(array $values): PeriodTotalsInterface
    {
        return $this->totalsFactory->create()
            ->setRevenue($values['revenue'])
            ->setOrders($values['orders'])
            ->setAverageOrder($this->calculator->average($values['revenue'], $values['orders']));
    }
}
