<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Api\Data;

/**
 * Order summary without any personal data (no name, no email, no full address).
 *
 * @api
 */
interface OrderSummaryInterface
{
    public const ENTITY_ID = 'entity_id';
    public const INCREMENT_ID = 'increment_id';
    public const AMOUNT = 'amount';
    public const CITY = 'city';
    public const ITEMS = 'items';
    public const CREATED_AT = 'created_at';

    /**
     * @return int
     */
    public function getEntityId();

    /**
     * @param int $entityId
     * @return $this
     */
    public function setEntityId($entityId);

    /**
     * @return string
     */
    public function getIncrementId();

    /**
     * @param string $incrementId
     * @return $this
     */
    public function setIncrementId($incrementId);

    /**
     * Order total incl. tax (base_grand_total), in base currency.
     *
     * @return float
     */
    public function getAmount();

    /**
     * @param float $amount
     * @return $this
     */
    public function setAmount($amount);

    /**
     * Shipping city (billing city for orders without shipping), or an empty string.
     *
     * @return string
     */
    public function getCity();

    /**
     * @param string $city
     * @return $this
     */
    public function setCity($city);

    /**
     * Number of items (total quantity ordered).
     *
     * @return int
     */
    public function getItems();

    /**
     * @param int $items
     * @return $this
     */
    public function setItems($items);

    /**
     * ISO 8601 date in the configured timezone (e.g. 2026-11-16T14:03:12+01:00).
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);
}
