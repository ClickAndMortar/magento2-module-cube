<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Screens implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'today', 'label' => __("CA et commandes du jour")],
            ['value' => 'compare', 'label' => __("Comparaison avec hier à la même heure")],
            ['value' => 'week', 'label' => __("Courbe des 7 derniers jours")],
            ['value' => 'last_order', 'label' => __("Dernière commande")],
            ['value' => 'goal', 'label' => __("Objectif du jour")],
        ];
    }
}
