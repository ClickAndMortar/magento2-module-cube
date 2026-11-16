<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model;

/**
 * Horloge injectable (remplacée dans les tests).
 */
class Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
