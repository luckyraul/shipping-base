<?php

/**
 * @author Mygento Team
 * @copyright Copyright 2017 Mygento (https://www.mygento.ru)
 * @package Mygento_Shipment
 */

namespace Mygento\Shipment\Model\Source;

class Dimensionunit implements \Magento\Framework\Option\ArrayInterface
{
    
    /**
     * Possible weight units
     * TODO i18n
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 100, 'label' => __('Meter')],
            ['value' => 1, 'label' => __('Centimeter')]
        ];
    }
}
