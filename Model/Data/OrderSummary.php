<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model\Data;

use ClickAndMortar\Cube\Api\Data\OrderSummaryInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class OrderSummary extends AbstractSimpleObject implements OrderSummaryInterface
{
    public function getEntityId()
    {
        return (int) $this->_get(self::ENTITY_ID);
    }

    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, (int) $entityId);
    }

    public function getIncrementId()
    {
        return (string) $this->_get(self::INCREMENT_ID);
    }

    public function setIncrementId($incrementId)
    {
        return $this->setData(self::INCREMENT_ID, (string) $incrementId);
    }

    public function getAmount()
    {
        return (float) $this->_get(self::AMOUNT);
    }

    public function setAmount($amount)
    {
        return $this->setData(self::AMOUNT, (float) $amount);
    }

    public function getCity()
    {
        return (string) $this->_get(self::CITY);
    }

    public function setCity($city)
    {
        return $this->setData(self::CITY, (string) $city);
    }

    public function getItems()
    {
        return (int) $this->_get(self::ITEMS);
    }

    public function setItems($items)
    {
        return $this->setData(self::ITEMS, (int) $items);
    }

    public function getCreatedAt()
    {
        return (string) $this->_get(self::CREATED_AT);
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, (string) $createdAt);
    }
}
