<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Api\Data;

/**
 * @api
 */
interface OrderEventInterface extends OrderSummaryInterface
{
    public const FIRST_OF_DAY = 'first_of_day';
    public const RECORD_BROKEN = 'record_broken';

    /**
     * Première commande de la journée locale (aujourd'hui seulement).
     *
     * @return bool
     */
    public function getFirstOfDay();

    /**
     * @param bool $firstOfDay
     * @return $this
     */
    public function setFirstOfDay($firstOfDay);

    /**
     * Cette commande fait passer le CA du jour au-dessus du record journalier.
     *
     * @return bool
     */
    public function getRecordBroken();

    /**
     * @param bool $recordBroken
     * @return $this
     */
    public function setRecordBroken($recordBroken);
}
