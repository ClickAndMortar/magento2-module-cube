<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Api;

/**
 * New orders since a given entity ID, used to trigger the cube's animations.
 *
 * @api
 */
interface EventsInterface
{
    /**
     * Orders whose entity_id is strictly greater than $since, in ascending order.
     * With since=0 no order is returned: only the last_entity_id cursor is filled.
     *
     * @param int $since last entity_id already seen by the cube
     * @param int $limit maximum number of orders (0 = configured value, capped at 100)
     * @return \ClickAndMortar\Cube\Api\Data\EventsResultInterface
     * @throws \Magento\Framework\Webapi\Exception 403 when the cube API is disabled
     */
    public function getEvents($since = 0, $limit = 0);
}
