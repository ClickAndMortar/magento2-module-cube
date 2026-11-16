<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Api\Data;

/**
 * @api
 */
interface EventsResultInterface
{
    public const EVENTS = 'events';
    public const LAST_ENTITY_ID = 'last_entity_id';
    public const SKIPPED = 'skipped';

    /**
     * New orders, by ascending entity_id.
     *
     * @return \ClickAndMortar\Cube\Api\Data\OrderEventInterface[]
     */
    public function getEvents();

    /**
     * @param \ClickAndMortar\Cube\Api\Data\OrderEventInterface[] $events
     * @return $this
     */
    public function setEvents(array $events);

    /**
     * Cursor to send back on the next call (since=...).
     *
     * @return int
     */
    public function getLastEntityId();

    /**
     * @param int $lastEntityId
     * @return $this
     */
    public function setLastEntityId($lastEntityId);

    /**
     * Older orders not returned because of the limit.
     *
     * @return int
     */
    public function getSkipped();

    /**
     * @param int $skipped
     * @return $this
     */
    public function setSkipped($skipped);
}
