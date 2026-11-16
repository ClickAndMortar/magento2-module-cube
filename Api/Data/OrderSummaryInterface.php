<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Api\Data;

/**
 * Résumé d'une commande, sans donnée personnelle (ni nom, ni e-mail, ni adresse complète).
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
     * Montant TTC (base_grand_total), en devise de base.
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
     * Ville de livraison (de facturation pour une commande sans livraison), ou chaîne vide.
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
     * Nombre d'articles (quantité totale commandée).
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
     * Date ISO 8601 dans le fuseau configuré (ex. 2026-11-16T14:03:12+01:00).
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
