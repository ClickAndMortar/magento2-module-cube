<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Api\Data;

/**
 * @api
 */
interface PeriodTotalsInterface
{
    public const REVENUE = 'revenue';
    public const ORDERS = 'orders';
    public const AVERAGE_ORDER = 'average_order';

    /**
     * Revenue (sum of base_grand_total), in base currency.
     *
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

    /**
     * Average order value (0 when there is no order).
     *
     * @return float
     */
    public function getAverageOrder();

    /**
     * @param float $averageOrder
     * @return $this
     */
    public function setAverageOrder($averageOrder);
}
