<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Test\Unit;

use ClickAndMortar\Cube\Model\PeriodResolver;
use PHPUnit\Framework\TestCase;

class PeriodResolverTest extends TestCase
{
    /**
     * @dataProvider casesProvider
     */
    public function testResolve(
        string $tz,
        string $nowUtc,
        string $todayDate,
        string $todayStart,
        string $tomorrowStart,
        string $yesterdayStart,
        string $yesterdaySameTime
    ): void {
        $periods = (new PeriodResolver())->resolve(
            new \DateTimeZone($tz),
            new \DateTimeImmutable($nowUtc, new \DateTimeZone('UTC'))
        );
        self::assertSame($todayDate, $periods->todayDate);
        self::assertSame($todayStart, $periods->todayStartUtc);
        self::assertSame($tomorrowStart, $periods->tomorrowStartUtc);
        self::assertSame($yesterdayStart, $periods->yesterdayStartUtc);
        self::assertSame($yesterdaySameTime, $periods->yesterdaySameTimeUtc);
    }

    public static function casesProvider(): array
    {
        return [
            'Paris, hiver' => [
                'Europe/Paris', '2026-11-16 13:00:00', '2026-11-16',
                '2026-11-15 23:00:00', '2026-11-16 23:00:00', '2026-11-14 23:00:00', '2026-11-15 13:00:00',
            ],
            'Paris, juste après minuit local' => [
                'Europe/Paris', '2026-11-15 23:30:00', '2026-11-16',
                '2026-11-15 23:00:00', '2026-11-16 23:00:00', '2026-11-14 23:00:00', '2026-11-14 23:30:00',
            ],
            'Paris, passage à l\'heure d\'été (journée de 23 h)' => [
                'Europe/Paris', '2026-03-29 10:00:00', '2026-03-29',
                '2026-03-28 23:00:00', '2026-03-29 22:00:00', '2026-03-27 23:00:00', '2026-03-28 11:00:00',
            ],
            'Paris, lendemain du passage à l\'heure d\'été' => [
                'Europe/Paris', '2026-03-30 10:00:00', '2026-03-30',
                '2026-03-29 22:00:00', '2026-03-30 22:00:00', '2026-03-28 23:00:00', '2026-03-29 10:00:00',
            ],
            'Paris, passage à l\'heure d\'hiver (journée de 25 h)' => [
                'Europe/Paris', '2026-10-25 12:00:00', '2026-10-25',
                '2026-10-24 22:00:00', '2026-10-25 23:00:00', '2026-10-23 22:00:00', '2026-10-24 11:00:00',
            ],
            'UTC' => [
                'UTC', '2026-11-16 08:15:00', '2026-11-16',
                '2026-11-16 00:00:00', '2026-11-17 00:00:00', '2026-11-15 00:00:00', '2026-11-15 08:15:00',
            ],
            'Kolkata, décalage d\'une demi-heure' => [
                'Asia/Kolkata', '2026-11-16 20:00:00', '2026-11-17',
                '2026-11-16 18:30:00', '2026-11-17 18:30:00', '2026-11-15 18:30:00', '2026-11-15 20:00:00',
            ],
        ];
    }

    public function testShiftDateAndLocalConversions(): void
    {
        $periods = (new PeriodResolver())->resolve(
            new \DateTimeZone('Europe/Paris'),
            new \DateTimeImmutable('2026-03-31 10:00:00', new \DateTimeZone('UTC'))
        );
        self::assertSame('2026-03-25', $periods->shiftDate(-6));
        self::assertSame('2026-03-28 23:00:00', $periods->dayStartUtc('2026-03-29'));
        self::assertSame('2026-03-29 22:00:00', $periods->dayStartUtc('2026-03-30'));
        self::assertSame('2026-03-31T12:00:00+02:00', $periods->toLocalIso('2026-03-31 10:00:00'));
        self::assertSame('2026-03-30', $periods->toLocalDate('2026-03-29 22:30:00'));
        self::assertSame('2026-03-31T12:00:00+02:00', $periods->nowLocal->format(\DATE_ATOM));
    }
}
