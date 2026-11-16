<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model;

use ClickAndMortar\Cube\Api\Data\OrderEventInterface;
use ClickAndMortar\Cube\Api\Data\OrderEventInterfaceFactory;
use ClickAndMortar\Cube\Api\Data\OrderSummaryInterface;
use ClickAndMortar\Cube\Api\Data\OrderSummaryInterfaceFactory;

/**
 * SQL row -> API object (rounded amount, integer quantity, date in the configured timezone).
 */
class OrderSummaryMapper
{
    public function __construct(
        private readonly OrderSummaryInterfaceFactory $summaryFactory,
        private readonly OrderEventInterfaceFactory $eventFactory
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public function summary(array $row, Periods $periods): OrderSummaryInterface
    {
        /** @var OrderSummaryInterface $summary */
        $summary = $this->summaryFactory->create();
        return $this->fill($summary, $row, $periods);
    }

    /**
     * @param array<string, mixed> $row
     */
    public function event(array $row, Periods $periods, bool $firstOfDay, bool $recordBroken): OrderEventInterface
    {
        /** @var OrderEventInterface $event */
        $event = $this->eventFactory->create();
        $this->fill($event, $row, $periods);
        $event->setFirstOfDay($firstOfDay)->setRecordBroken($recordBroken);
        return $event;
    }

    /**
     * @template T of OrderSummaryInterface
     * @param T $target
     * @param array<string, mixed> $row
     * @return T
     */
    private function fill(OrderSummaryInterface $target, array $row, Periods $periods): OrderSummaryInterface
    {
        $target->setEntityId((int) $row['entity_id'])
            ->setIncrementId((string) $row['increment_id'])
            ->setAmount(round((float) $row['amount'], 2))
            ->setCity((string) ($row['city'] ?? ''))
            ->setItems((int) round((float) ($row['items'] ?? 0)))
            ->setCreatedAt($periods->toLocalIso((string) $row['created_at']));
        return $target;
    }
}
