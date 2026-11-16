<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Api;

/**
 * Agrégats de ventes du jour pour le cube.
 *
 * @api
 */
interface StatsInterface
{
    /**
     * Statistiques du jour (fuseau configuré), comparaison avec hier à la même heure, 7 derniers jours,
     * record et dernière commande. Aucune donnée personnelle.
     *
     * @return \ClickAndMortar\Cube\Api\Data\StatsResultInterface
     * @throws \Magento\Framework\Webapi\Exception 403 si l'API du cube est désactivée
     */
    public function getStats();
}
