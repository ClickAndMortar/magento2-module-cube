<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model\Data;

use ClickAndMortar\Cube\Api\Data\OrderSummaryInterface;
use ClickAndMortar\Cube\Api\Data\PeriodTotalsInterface;
use ClickAndMortar\Cube\Api\Data\StatsResultInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class StatsResult extends AbstractSimpleObject implements StatsResultInterface
{
    public function getGeneratedAt()
    {
        return (string) $this->_get(self::GENERATED_AT);
    }

    public function setGeneratedAt($generatedAt)
    {
        return $this->setData(self::GENERATED_AT, (string) $generatedAt);
    }

    public function getTimezone()
    {
        return (string) $this->_get(self::TIMEZONE);
    }

    public function setTimezone($timezone)
    {
        return $this->setData(self::TIMEZONE, (string) $timezone);
    }

    public function getCurrency()
    {
        return (string) $this->_get(self::CURRENCY);
    }

    public function setCurrency($currency)
    {
        return $this->setData(self::CURRENCY, (string) $currency);
    }

    public function getStoreIds()
    {
        return $this->_get(self::STORE_IDS) ?? [];
    }

    public function setStoreIds(array $storeIds)
    {
        return $this->setData(self::STORE_IDS, array_values(array_map('intval', $storeIds)));
    }

    public function getScreens()
    {
        return $this->_get(self::SCREENS) ?? [];
    }

    public function setScreens(array $screens)
    {
        return $this->setData(self::SCREENS, array_values(array_map('strval', $screens)));
    }

    public function getToday()
    {
        return $this->_get(self::TODAY);
    }

    public function setToday(PeriodTotalsInterface $today)
    {
        return $this->setData(self::TODAY, $today);
    }

    public function getYesterdaySameTime()
    {
        return $this->_get(self::YESTERDAY_SAME_TIME);
    }

    public function setYesterdaySameTime(PeriodTotalsInterface $totals)
    {
        return $this->setData(self::YESTERDAY_SAME_TIME, $totals);
    }

    public function getRevenueDeltaPct()
    {
        $value = $this->_get(self::REVENUE_DELTA_PCT);
        return $value === null ? null : (float) $value;
    }

    public function setRevenueDeltaPct($pct)
    {
        return $this->setData(self::REVENUE_DELTA_PCT, $pct === null ? null : (float) $pct);
    }

    public function getOrdersDeltaPct()
    {
        $value = $this->_get(self::ORDERS_DELTA_PCT);
        return $value === null ? null : (float) $value;
    }

    public function setOrdersDeltaPct($pct)
    {
        return $this->setData(self::ORDERS_DELTA_PCT, $pct === null ? null : (float) $pct);
    }

    public function getGoalRevenue()
    {
        $value = $this->_get(self::GOAL_REVENUE);
        return $value === null ? null : (float) $value;
    }

    public function setGoalRevenue($goal)
    {
        return $this->setData(self::GOAL_REVENUE, $goal === null ? null : (float) $goal);
    }

    public function getGoalProgressPct()
    {
        $value = $this->_get(self::GOAL_PROGRESS_PCT);
        return $value === null ? null : (float) $value;
    }

    public function setGoalProgressPct($pct)
    {
        return $this->setData(self::GOAL_PROGRESS_PCT, $pct === null ? null : (float) $pct);
    }

    public function getRecordRevenue()
    {
        $value = $this->_get(self::RECORD_REVENUE);
        return $value === null ? null : (float) $value;
    }

    public function setRecordRevenue($revenue)
    {
        return $this->setData(self::RECORD_REVENUE, $revenue === null ? null : (float) $revenue);
    }

    public function getRecordDate()
    {
        $value = $this->_get(self::RECORD_DATE);
        return $value === null ? null : (string) $value;
    }

    public function setRecordDate($date)
    {
        return $this->setData(self::RECORD_DATE, $date === null ? null : (string) $date);
    }

    public function getRecordBrokenToday()
    {
        return (bool) $this->_get(self::RECORD_BROKEN_TODAY);
    }

    public function setRecordBrokenToday($broken)
    {
        return $this->setData(self::RECORD_BROKEN_TODAY, (bool) $broken);
    }

    public function getWeek()
    {
        return $this->_get(self::WEEK) ?? [];
    }

    public function setWeek(array $days)
    {
        return $this->setData(self::WEEK, array_values($days));
    }

    public function getLastOrder()
    {
        return $this->_get(self::LAST_ORDER);
    }

    public function setLastOrder(?OrderSummaryInterface $order = null)
    {
        return $this->setData(self::LAST_ORDER, $order);
    }

    public function getLastEntityId()
    {
        return (int) $this->_get(self::LAST_ENTITY_ID);
    }

    public function setLastEntityId($lastEntityId)
    {
        return $this->setData(self::LAST_ENTITY_ID, (int) $lastEntityId);
    }
}
