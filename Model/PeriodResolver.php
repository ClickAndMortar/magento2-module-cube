<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model;

/**
 * Calcule « aujourd'hui », « hier à la même heure », etc. dans un fuseau donné,
 * en tenant compte des changements d'heure (journées de 23 h ou 25 h).
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

        // Même heure murale hier : on recompose la date pour ne pas dépendre de la durée de la journée.
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
