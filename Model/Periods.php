<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model;

/**
 * Time boundaries of a computation, in UTC (MySQL format) like sales_order.created_at.
 * Start boundaries are inclusive, end boundaries exclusive.
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
     * Local midnight of $localDate (YYYY-MM-DD), in UTC.
     */
    public function dayStartUtc(string $localDate): string
    {
        return (new \DateTimeImmutable($localDate . ' 00:00:00', $this->timezone))
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format(self::SQL_FORMAT);
    }

    /**
     * Local date shifted by $days days (negative = past).
     */
    public function shiftDate(int $days, ?string $fromDate = null): string
    {
        $date = new \DateTimeImmutable(($fromDate ?? $this->todayDate) . ' 12:00:00', $this->timezone);
        return $date->modify(sprintf('%+d day', $days))->format('Y-m-d');
    }

    /**
     * Converts a UTC database date to ISO 8601 in the configured timezone.
     */
    public function toLocalIso(string $utcSqlDate): string
    {
        return (new \DateTimeImmutable($utcSqlDate, new \DateTimeZone('UTC')))
            ->setTimezone($this->timezone)
            ->format(\DateTimeInterface::ATOM);
    }

    /**
     * Local date (YYYY-MM-DD) of a UTC database date.
     */
    public function toLocalDate(string $utcSqlDate): string
    {
        return (new \DateTimeImmutable($utcSqlDate, new \DateTimeZone('UTC')))
            ->setTimezone($this->timezone)
            ->format('Y-m-d');
    }
}
