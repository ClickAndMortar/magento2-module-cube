<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Screens implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'today', 'label' => __('Today\'s revenue and orders')],
            ['value' => 'compare', 'label' => __('Comparison with yesterday at the same time')],
            ['value' => 'week', 'label' => __('Last 7 days chart')],
            ['value' => 'last_order', 'label' => __('Last order')],
            ['value' => 'goal', 'label' => __('Daily goal')],
        ];
    }
}
