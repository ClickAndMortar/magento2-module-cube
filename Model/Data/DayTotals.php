<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model\Data;

use ClickAndMortar\Cube\Api\Data\DayTotalsInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class DayTotals extends AbstractSimpleObject implements DayTotalsInterface
{
    public function getDate()
    {
        return (string) $this->_get(self::DATE);
    }

    public function setDate($date)
    {
        return $this->setData(self::DATE, (string) $date);
    }

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
}
