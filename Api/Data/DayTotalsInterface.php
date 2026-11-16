<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Api\Data;

/**
 * @api
 */
interface DayTotalsInterface
{
    public const DATE = 'date';
    public const REVENUE = 'revenue';
    public const ORDERS = 'orders';

    /**
     * Date locale (fuseau configuré), au format AAAA-MM-JJ.
     *
     * @return string
     */
    public function getDate();

    /**
     * @param string $date
     * @return $this
     */
    public function setDate($date);

    /**
     * @return float
     */
    public function getRevenue();

    /**
     * @param float $revenue
     * @return $this
     */
    public function setRevenue($revenue);

    /**
     * @return int
     */
    public function getOrders();

    /**
     * @param int $orders
     * @return $this
     */
    public function setOrders($orders);
}
