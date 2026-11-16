<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model\Data;

use ClickAndMortar\Cube\Api\Data\PeriodTotalsInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class PeriodTotals extends AbstractSimpleObject implements PeriodTotalsInterface
{
    public function getRevenue()
    {
        return (float) $this->_get(self::REVENUE);
    }

    public function setRevenue($revenue)
    {
        return $this->setData(self::REVENUE, (float) $revenue);
    }

    public function getOrders()
    {
        return (int) $this->_get(self::ORDERS);
    }

    public function setOrders($orders)
    {
        return $this->setData(self::ORDERS, (int) $orders);
    }

    public function getAverageOrder()
    {
        return (float) $this->_get(self::AVERAGE_ORDER);
    }

    public function setAverageOrder($averageOrder)
    {
        return $this->setData(self::AVERAGE_ORDER, (float) $averageOrder);
    }
}
