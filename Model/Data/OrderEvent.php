<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model\Data;

use ClickAndMortar\Cube\Api\Data\OrderEventInterface;

class OrderEvent extends OrderSummary implements OrderEventInterface
{
    public function getFirstOfDay()
    {
        return (bool) $this->_get(self::FIRST_OF_DAY);
    }

    public function setFirstOfDay($firstOfDay)
    {
        return $this->setData(self::FIRST_OF_DAY, (bool) $firstOfDay);
    }

    public function getRecordBroken()
    {
        return (bool) $this->_get(self::RECORD_BROKEN);
    }

    public function setRecordBroken($recordBroken)
    {
        return $this->setData(self::RECORD_BROKEN, (bool) $recordBroken);
    }
}
