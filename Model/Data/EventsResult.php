<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model\Data;

use ClickAndMortar\Cube\Api\Data\EventsResultInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class EventsResult extends AbstractSimpleObject implements EventsResultInterface
{
    public function getEvents()
    {
        return $this->_get(self::EVENTS) ?? [];
    }

    public function setEvents(array $events)
    {
        return $this->setData(self::EVENTS, array_values($events));
    }

    public function getLastEntityId()
    {
        return (int) $this->_get(self::LAST_ENTITY_ID);
    }

    public function setLastEntityId($lastEntityId)
    {
        return $this->setData(self::LAST_ENTITY_ID, (int) $lastEntityId);
    }

    public function getSkipped()
    {
        return (int) $this->_get(self::SKIPPED);
    }

    public function setSkipped($skipped)
    {
        return $this->setData(self::SKIPPED, (int) $skipped);
    }
}
