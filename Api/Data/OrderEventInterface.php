<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Api\Data;

/**
 * @api
 */
interface OrderEventInterface extends OrderSummaryInterface
{
    public const FIRST_OF_DAY = 'first_of_day';
    public const RECORD_BROKEN = 'record_broken';

    /**
     * First counted order of the local day (today only).
     *
     * @return bool
     */
    public function getFirstOfDay();

    /**
     * @param bool $firstOfDay
     * @return $this
     */
    public function setFirstOfDay($firstOfDay);

    /**
     * This order pushes today's revenue above the daily record.
     *
     * @return bool
     */
    public function getRecordBroken();

    /**
     * @param bool $recordBroken
     * @return $this
     */
    public function setRecordBroken($recordBroken);
}
