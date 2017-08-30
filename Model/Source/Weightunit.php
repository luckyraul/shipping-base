<?php

/**
 * @author Mygento Team
 * @copyright Copyright 2017 Mygento (https://www.mygento.ru)
 * @package Mygento_Shipment
 */

namespace Mygento\Shipment\Model\Source;

class Weightunit implements \Magento\Framework\Option\ArrayInterface
{
    
    /**
     * Possible weight units
     * TODO i18n
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 1000, 'label' => __('Gram')],
            ['value' => 1, 'label' => __('Kilogram')]
        ];
    }
}
