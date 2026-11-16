<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Sales\Model\Order\Config as OrderConfig;

class OrderStates implements OptionSourceInterface
{
    public function __construct(private readonly OrderConfig $orderConfig)
    {
    }

    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->orderConfig->getStates() as $code => $label) {
            $options[] = ['value' => $code, 'label' => sprintf('%s (%s)', $label, $code)];
        }
        return $options;
    }
}
