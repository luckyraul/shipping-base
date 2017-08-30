<?php
/**
 * @author Mygento Team
 * @copyright 2017 Mygento (https://www.mygento.ru)
 * @package Mygento_Shipment
 */

namespace Mygento\Shipment\Model\Source;

class Dimensionunits implements \Magento\Framework\Option\ArrayInterface {
    public function toOptionArray() {
        return [
            ['value' => 100, 'label' => __('meter')],
            ['value' => 1, 'label' => __('centimeter')]
        ];
    }

}
