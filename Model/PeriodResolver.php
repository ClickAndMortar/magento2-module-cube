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
        $yesterdayStart = self::localMidnightUtc($yesterdayDate, $timezone);
        $todayStart = self::localMidnightUtc($todayDate, $timezone);
        $sameTimeUtc = self::toUtc($sameTime);
        if ($sameTimeUtc > $todayStart) {
            $sameTimeUtc = $todayStart;
        }

        return new Periods(
            $timezone,
            $nowLocal,
            $todayDate,
            $todayStart,
            self::localMidnightUtc($tomorrowDate, $timezone),
            $yesterdayStart,
            $sameTimeUtc
        );
    }

    public static function localMidnightUtc(string $localDate, \DateTimeZone $timezone): string
    {
        return self::toUtc(new \DateTimeImmutable($localDate . ' 00:00:00', $timezone));
    }

    private static function toUtc(\DateTimeImmutable $date): string
    {
        return $date->setTimezone(new \DateTimeZone('UTC'))->format(Periods::SQL_FORMAT);
    }
}
