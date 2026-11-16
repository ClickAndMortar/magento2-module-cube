<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Api;

/**
 * Today's sales aggregates for the cube.
 *
 * @api
 */
interface StatsInterface
{
    /**
     * Today's figures (configured timezone), comparison with yesterday at the same time, last 7 days,
     * record and last order. No personal data.
     *
     * @return \ClickAndMortar\Cube\Api\Data\StatsResultInterface
     * @throws \Magento\Framework\Webapi\Exception 403 when the cube API is disabled
     */
    public function getStats();
}
