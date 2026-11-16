<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model;

/**
 * Pure computations (no database): grouping by local day, deltas, record, event flags.
 */
class StatsCalculator
{
    /**
     * Groups 15-minute UTC buckets ("YYYY-MM-DD HH:MM") into local days.
     *
     * @param array<int, array{bucket: string, revenue: float|string, orders: int|string}> $buckets
     * @return array<string, array{revenue: float, orders: int}> key = local date
     */
    public function aggregateDays(array $buckets, \DateTimeZone $timezone): array
    {
        $utc = new \DateTimeZone('UTC');
        $days = [];
        foreach ($buckets as $row) {
            $date = (new \DateTimeImmutable($row['bucket'] . ':00', $utc))->setTimezone($timezone)->format('Y-m-d');
            $days[$date]['revenue'] = ($days[$date]['revenue'] ?? 0.0) + (float) $row['revenue'];
            $days[$date]['orders'] = ($days[$date]['orders'] ?? 0) + (int) $row['orders'];
        }
        ksort($days);
        foreach ($days as $date => $values) {
            $days[$date]['revenue'] = round($values['revenue'], 2);
        }
        return $days;
    }

    /**
     * Change in % of $current vs $previous, rounded to 0.1; null when $previous is zero.
     */
    public function deltaPct(float $current, float $previous): ?float
    {
        if ($previous <= 0.0) {
            return null;
        }
        return round(($current - $previous) / $previous * 100, 1);
    }

    public function average(float $revenue, int $orders): float
    {
        return $orders > 0 ? round($revenue / $orders, 2) : 0.0;
    }

    /**
     * Best day (most recent one on ties), or null when there are no sales.
     *
     * @param array<string, array{revenue: float, orders: int}> $days
     * @return array{date: string, revenue: float}|null
     */
    public function record(array $days): ?array
    {
        $best = null;
        foreach ($days as $date => $values) {
            if ($values['revenue'] <= 0) {
                continue;
            }
            if ($best === null || $values['revenue'] >= $best['revenue']) {
                $best = ['date' => (string) $date, 'revenue' => (float) $values['revenue']];
            }
        }
        return $best;
    }

    public function goalProgress(float $revenue, float $goal): ?float
    {
        return $goal > 0 ? round($revenue / $goal * 100, 1) : null;
    }

    /**
     * "First order of the day" and "record broken" flags for orders sorted by ascending entity_id.
     *
     * @param array<int, array{entity_id: int, amount: float, is_today: bool}> $orders
     * @param int|null $firstTodayEntityId lowest entity_id counted today
     * @param float $revenueBeforeFirstToday today's revenue from orders before the list's first order of today
     * @param float|null $record best daily revenue, today excluded
     * @return array<int, array{first_of_day: bool, record_broken: bool}> key = entity_id
     */
    public function eventFlags(
        array $orders,
        ?int $firstTodayEntityId,
        float $revenueBeforeFirstToday,
        ?float $record
    ): array {
        $flags = [];
        $cumulative = $revenueBeforeFirstToday;
        foreach ($orders as $order) {
            $id = (int) $order['entity_id'];
            $flags[$id] = ['first_of_day' => false, 'record_broken' => false];
            if (!$order['is_today']) {
                continue;
            }
            $flags[$id]['first_of_day'] = $firstTodayEntityId !== null && $id === $firstTodayEntityId;
            $before = $cumulative;
            $cumulative += (float) $order['amount'];
            $flags[$id]['record_broken'] = $record !== null && $record > 0
                && round($before, 2) <= $record && round($cumulative, 2) > $record;
        }
        return $flags;
    }
}
