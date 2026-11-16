<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model;

use ClickAndMortar\Cube\Api\Data\EventsResultInterface;
use ClickAndMortar\Cube\Api\Data\EventsResultInterfaceFactory;
use ClickAndMortar\Cube\Api\EventsInterface;
use ClickAndMortar\Cube\Model\ResourceModel\OrderStats;
use Magento\Framework\Webapi\Exception as WebapiException;

class EventsService implements EventsInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly Clock $clock,
        private readonly PeriodResolver $periodResolver,
        private readonly OrderStats $orderStats,
        private readonly History $history,
        private readonly StatsCalculator $calculator,
        private readonly OrderSummaryMapper $mapper,
        private readonly EventsResultInterfaceFactory $resultFactory
    ) {
    }

    public function getEvents($since = 0, $limit = 0)
    {
        if (!$this->config->isEnabled()) {
            throw new WebapiException(__('The cube API is disabled.'), 0, WebapiException::HTTP_FORBIDDEN);
        }

        $since = max(0, (int) $since);
        $limit = (int) $limit > 0 ? min((int) $limit, Config::MAX_EVENTS) : $this->config->getEventsLimit();
        $storeIds = $this->config->getStoreIds();
        $states = $this->config->getExcludedStates();

        /** @var EventsResultInterface $result */
        $result = $this->resultFactory->create();
        $result->setEvents([])->setSkipped(0);

        $maxId = $this->orderStats->maxEntityId($storeIds, $states);
        $rows = $since > 0 ? $this->orderStats->ordersSince($since, $limit, $storeIds, $states) : [];
        if ($rows === []) {
            // since=0: first call, only hand out the cursor.
            // since > max: database restored or orders deleted, realign the cursor.
            return $result->setLastEntityId($since > 0 ? min($since, $maxId) : $maxId);
        }

        $periods = $this->periodResolver->resolve($this->config->getTimezone(), $this->clock->now());
        $orders = [];
        $firstTodayInList = null;
        foreach ($rows as $row) {
            $isToday = $row['created_at'] >= $periods->todayStartUtc && $row['created_at'] < $periods->tomorrowStartUtc;
            if ($isToday && $firstTodayInList === null) {
                $firstTodayInList = (int) $row['entity_id'];
            }
            $orders[] = ['entity_id' => (int) $row['entity_id'], 'amount' => (float) $row['amount'], 'is_today' => $isToday];
        }

        $firstOfDay = null;
        $revenueBefore = 0.0;
        $record = null;
        if ($firstTodayInList !== null) {
            $firstOfDay = $this->orderStats->minEntityIdBetween(
                $periods->todayStartUtc,
                $periods->tomorrowStartUtc,
                $storeIds,
                $states
            );
            $revenueBefore = $this->orderStats->revenueBefore(
                $periods->todayStartUtc,
                $periods->tomorrowStartUtc,
                $firstTodayInList,
                $storeIds,
                $states
            );
            $record = $this->history->record($periods)['revenue'] ?? null;
        }
        $flags = $this->calculator->eventFlags($orders, $firstOfDay, $revenueBefore, $record);

        $events = [];
        foreach ($rows as $row) {
            $id = (int) $row['entity_id'];
            $events[] = $this->mapper->event($row, $periods, $flags[$id]['first_of_day'], $flags[$id]['record_broken']);
        }

        $total = $this->orderStats->countSince($since, $storeIds, $states);
        return $result->setEvents($events)
            ->setLastEntityId((int) end($rows)['entity_id'])
            ->setSkipped(max(0, $total - count($rows)));
    }
}
