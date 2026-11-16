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
     * Nouvelles commandes, par entity_id croissant.
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
     * Curseur à renvoyer au prochain appel (since=…).
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
     * Commandes plus anciennes non renvoyées à cause de la limite.
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
