<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Api\Data;

/**
 * @api
 */
interface StatsResultInterface
{
    public const GENERATED_AT = 'generated_at';
    public const TIMEZONE = 'timezone';
    public const CURRENCY = 'currency';
    public const STORE_IDS = 'store_ids';
    public const SCREENS = 'screens';
    public const TODAY = 'today';
    public const YESTERDAY_SAME_TIME = 'yesterday_same_time';
    public const REVENUE_DELTA_PCT = 'revenue_delta_pct';
    public const ORDERS_DELTA_PCT = 'orders_delta_pct';
    public const GOAL_REVENUE = 'goal_revenue';
    public const GOAL_PROGRESS_PCT = 'goal_progress_pct';
    public const RECORD_REVENUE = 'record_revenue';
    public const RECORD_DATE = 'record_date';
    public const RECORD_BROKEN_TODAY = 'record_broken_today';
    public const WEEK = 'week';
    public const LAST_ORDER = 'last_order';
    public const LAST_ENTITY_ID = 'last_entity_id';

    /**
     * ISO 8601 timestamp of the computation, in the configured timezone.
     *
     * @return string
     */
    public function getGeneratedAt();

    /**
     * @param string $generatedAt
     * @return $this
     */
    public function setGeneratedAt($generatedAt);

    /**
     * @return string
     */
    public function getTimezone();

    /**
     * @param string $timezone
     * @return $this
     */
    public function setTimezone($timezone);

    /**
     * ISO code of the base currency (e.g. EUR).
     *
     * @return string
     */
    public function getCurrency();

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency);

    /**
     * Store views taken into account (empty = all).
     *
     * @return int[]
     */
    public function getStoreIds();

    /**
     * @param int[] $storeIds
     * @return $this
     */
    public function setStoreIds(array $storeIds);

    /**
     * Enabled screens, in order: today, compare, week, last_order, goal.
     *
     * @return string[]
     */
    public function getScreens();

    /**
     * @param string[] $screens
     * @return $this
     */
    public function setScreens(array $screens);

    /**
     * @return \ClickAndMortar\Cube\Api\Data\PeriodTotalsInterface
     */
    public function getToday();

    /**
     * @param \ClickAndMortar\Cube\Api\Data\PeriodTotalsInterface $today
     * @return $this
     */
    public function setToday(PeriodTotalsInterface $today);

    /**
     * Yesterday, from midnight up to the same local time as now.
     *
     * @return \ClickAndMortar\Cube\Api\Data\PeriodTotalsInterface
     */
    public function getYesterdaySameTime();

    /**
     * @param \ClickAndMortar\Cube\Api\Data\PeriodTotalsInterface $totals
     * @return $this
     */
    public function setYesterdaySameTime(PeriodTotalsInterface $totals);

    /**
     * Revenue change in % vs yesterday at the same time (null when yesterday = 0).
     *
     * @return float|null
     */
    public function getRevenueDeltaPct();

    /**
     * @param float|null $pct
     * @return $this
     */
    public function setRevenueDeltaPct($pct);

    /**
     * Order count change in % vs yesterday at the same time (null when yesterday = 0).
     *
     * @return float|null
     */
    public function getOrdersDeltaPct();

    /**
     * @param float|null $pct
     * @return $this
     */
    public function setOrdersDeltaPct($pct);

    /**
     * Daily revenue goal (null when none).
     *
     * @return float|null
     */
    public function getGoalRevenue();

    /**
     * @param float|null $goal
     * @return $this
     */
    public function setGoalRevenue($goal);

    /**
     * Goal progress in % (may exceed 100; null without a goal).
     *
     * @return float|null
     */
    public function getGoalProgressPct();

    /**
     * @param float|null $pct
     * @return $this
     */
    public function setGoalProgressPct($pct);

    /**
     * Best daily revenue within the window, today excluded (null when no sales).
     *
     * @return float|null
     */
    public function getRecordRevenue();

    /**
     * @param float|null $revenue
     * @return $this
     */
    public function setRecordRevenue($revenue);

    /**
     * Local date of the record (YYYY-MM-DD), or null.
     *
     * @return string|null
     */
    public function getRecordDate();

    /**
     * @param string|null $date
     * @return $this
     */
    public function setRecordDate($date);

    /**
     * Today's revenue already exceeds the record.
     *
     * @return bool
     */
    public function getRecordBrokenToday();

    /**
     * @param bool $broken
     * @return $this
     */
    public function setRecordBrokenToday($broken);

    /**
     * The last 7 days, oldest first, today included (today = day so far).
     *
     * @return \ClickAndMortar\Cube\Api\Data\DayTotalsInterface[]
     */
    public function getWeek();

    /**
     * @param \ClickAndMortar\Cube\Api\Data\DayTotalsInterface[] $days
     * @return $this
     */
    public function setWeek(array $days);

    /**
     * Last counted order (any day), or null.
     *
     * @return \ClickAndMortar\Cube\Api\Data\OrderSummaryInterface|null
     */
    public function getLastOrder();

    /**
     * @param \ClickAndMortar\Cube\Api\Data\OrderSummaryInterface|null $order
     * @return $this
     */
    public function setLastOrder(?OrderSummaryInterface $order = null);

    /**
     * Highest counted entity_id: initial cursor for /V1/cm-cube/events.
     *
     * @return int
     */
    public function getLastEntityId();

    /**
     * @param int $lastEntityId
     * @return $this
     */
    public function setLastEntityId($lastEntityId);
}
