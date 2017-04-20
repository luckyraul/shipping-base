<?php

/**
 * @author Mygento Team
 * @copyright Copyright 2017 Mygento (https://www.mygento.ru)
 * @package Mygento_Yandexkassa
 */

namespace Mygento\Shipping\Model\Source;

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
            ['value' => 0.1, 'label' => __('Millimeter')],
            ['value' => 100, 'label' => __('Meter')],
            ['value' => 1, 'label' => __('Centimeter')]
        ];
    }

}
