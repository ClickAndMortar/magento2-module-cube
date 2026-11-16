<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model;

/**
 * Bornes temporelles d'un calcul, exprimées en UTC (format MySQL) comme sales_order.created_at.
 * Les bornes de début sont incluses, les bornes de fin exclues.
 */
class Periods
{
    public const SQL_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        public readonly \DateTimeZone $timezone,
        public readonly \DateTimeImmutable $nowLocal,
        public readonly string $todayDate,
        public readonly string $todayStartUtc,
        public readonly string $tomorrowStartUtc,
        public readonly string $yesterdayStartUtc,
        public readonly string $yesterdaySameTimeUtc
    ) {
    }

    /**
     * Minuit local de $localDate (AAAA-MM-JJ), en UTC.
     */
    public function dayStartUtc(string $localDate): string
    {
        return (new \DateTimeImmutable($localDate . ' 00:00:00', $this->timezone))
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format(self::SQL_FORMAT);
    }

    /**
     * Date locale décalée de $days jours (négatif = passé).
     */
    public function shiftDate(int $days, ?string $fromDate = null): string
    {
        $date = new \DateTimeImmutable(($fromDate ?? $this->todayDate) . ' 12:00:00', $this->timezone);
        return $date->modify(sprintf('%+d day', $days))->format('Y-m-d');
    }

    /**
     * Convertit une date UTC de la base en ISO 8601 dans le fuseau configuré.
     */
    public function toLocalIso(string $utcSqlDate): string
    {
        return (new \DateTimeImmutable($utcSqlDate, new \DateTimeZone('UTC')))
            ->setTimezone($this->timezone)
            ->format(\DateTimeInterface::ATOM);
    }

    /**
     * Date locale (AAAA-MM-JJ) d'une date UTC de la base.
     */
    public function toLocalDate(string $utcSqlDate): string
    {
        return (new \DateTimeImmutable($utcSqlDate, new \DateTimeZone('UTC')))
            ->setTimezone($this->timezone)
            ->format('Y-m-d');
    }
}
