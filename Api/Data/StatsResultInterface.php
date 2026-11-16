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
     * Horodatage ISO 8601 du calcul, dans le fuseau configuré.
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
     * Code ISO de la devise de base (ex. EUR).
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
     * Vues de boutique comptées (vide = toutes).
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
     * Écrans actifs, dans l'ordre : today, compare, week, last_order, goal.
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
     * Hier, de minuit jusqu'à la même heure locale qu'en ce moment.
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
     * Écart de CA en % avec hier à la même heure (null si hier = 0).
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
     * Écart du nombre de commandes en % avec hier à la même heure (null si hier = 0).
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
     * Objectif de CA du jour (null si aucun).
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
     * Avancement de l'objectif en % (peut dépasser 100 ; null sans objectif).
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
     * Meilleur CA journalier de la fenêtre, aujourd'hui exclu (null si aucune vente).
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
     * Date locale du record (AAAA-MM-JJ), ou null.
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
     * Le CA du jour dépasse déjà le record.
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
     * Les 7 derniers jours, du plus ancien à aujourd'hui inclus (aujourd'hui = journée en cours).
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
     * Dernière commande comptée (tous jours confondus), ou null.
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
     * Plus grand entity_id compté : curseur initial pour /V1/cm-cube/events.
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
