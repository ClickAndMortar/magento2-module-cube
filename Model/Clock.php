<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model;

/**
 * Injectable clock (replaced in tests).
 */
class Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
