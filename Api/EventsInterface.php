<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Api;

/**
 * Nouvelles commandes depuis un identifiant, pour les animations du cube.
 *
 * @api
 */
interface EventsInterface
{
    /**
     * Commandes dont l'entity_id est strictement supérieur à $since, par ordre croissant.
     * Avec since=0, aucune commande n'est renvoyée : seul le curseur last_entity_id est rempli.
     *
     * @param int $since dernier entity_id déjà vu par le cube
     * @param int $limit nombre maximal de commandes (0 = valeur configurée, plafonné à 100)
     * @return \ClickAndMortar\Cube\Api\Data\EventsResultInterface
     * @throws \Magento\Framework\Webapi\Exception 403 si l'API du cube est désactivée
     */
    public function getEvents($since = 0, $limit = 0);
}
