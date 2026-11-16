<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model;

/**
 * Computes "today", "yesterday at the same time", etc. in a given timezone,
 * DST-aware (23-hour and 25-hour days).
 */
class PeriodResolver
{
    public function resolve(\DateTimeZone $timezone, \DateTimeImmutable $now): Periods
    {
        $nowLocal = $now->setTimezone($timezone);
        $todayDate = $nowLocal->format('Y-m-d');
        $noon = new \DateTimeImmutable($todayDate . ' 12:00:00', $timezone);
        $tomorrowDate = $noon->modify('+1 day')->format('Y-m-d');
        $yesterdayDate = $noon->modify('-1 day')->format('Y-m-d');

        // Same wall-clock time yesterday: rebuild the date so the length of the day does not matter.
        $sameTime = new \DateTimeImmutable($yesterdayDate . ' ' . $nowLocal->format('H:i:s'), $timezone);
        $yesterdayStart = $this->localMidnightUtc($yesterdayDate, $timezone);
        $todayStart = $this->localMidnightUtc($todayDate, $timezone);
        $sameTimeUtc = $this->toUtc($sameTime);
        if ($sameTimeUtc > $todayStart) {
            $sameTimeUtc = $todayStart;
        }

        return new Periods(
            $timezone,
            $nowLocal,
            $todayDate,
            $todayStart,
            $this->localMidnightUtc($tomorrowDate, $timezone),
            $yesterdayStart,
            $sameTimeUtc
        );
    }

    private function localMidnightUtc(string $localDate, \DateTimeZone $timezone): string
    {
        return $this->toUtc(new \DateTimeImmutable($localDate . ' 00:00:00', $timezone));
    }

    private function toUtc(\DateTimeImmutable $date): string
    {
        return $date->setTimezone(new \DateTimeZone('UTC'))->format(Periods::SQL_FORMAT);
    }
}
