<?php
/**
 * @author Mygento
 * @copyright 2017 Mygento (https://www.mygento.ru)
 * @package Mygento_Shipment
 */

namespace Mygento\Shipment\Helper;

class Dimensions
{
    protected $_helper;

    public function __construct(
        \Mygento\Base\Helper\Data $helper
    ) {
        $this->_helper = $helper;
    }

    /**
     * Get items sizes
     * @return array
     */
    public function getItemsSizes($sizeCoefficient, $weightCoefficient, $object, $prefix = '')
    {
        $resultArray = [];

        if (!$object->getAllItems()) {
            return $resultArray;
        }

        foreach ($object->getAllItems() as $item) {
            if (!($item->getProduct() instanceof \Magento\Catalog\Model\Product)
                || $item->getParentItemId()) {
                continue;
            }

            $qty = $item->getQty();
            if ($object instanceof \Magento\Sales\Model\Order) {
                $qty = $item->getQtyOrdered();
            }

            for ($i = 1; $i <= $qty; $i++) {
                $productId = $item->getProductId();

                $itemArray = [];

                $itemArray['length'] = $this->getAttrValueByParam(
                    $prefix . 'length',
                    $productId
                );
                $itemArray['length'] = $this->_helper->formatToNumber($itemArray['length']);
                $itemArray['length'] = round($itemArray['length'] * $sizeCoefficient, 2);

                $itemArray['height'] = $this->getAttrValueByParam(
                    $prefix . 'height',
                    $productId
                );
                $itemArray['height'] = $this->_helper->formatToNumber($itemArray['length']);
                $itemArray['height'] = round($itemArray['length'] * $sizeCoefficient, 2);

                $itemArray['width'] = $this->getAttrValueByParam(
                    $prefix . 'width',
                    $productId
                );
                $itemArray['width'] = $this->_helper->formatToNumber($itemArray['length']);
                $itemArray['width'] = round($itemArray['length'] * $sizeCoefficient, 2);

                $itemArray['volume'] = $itemArray['length']
                    * $itemArray['height']
                    * $itemArray['width'];
                $itemArray['weight'] = round($item->getWeight() * $weightCoefficient, 2);

                $resultArray[] = $itemArray;
            }
        }

        return $resultArray;
    }

    /**
     * Fetch attribute code from $pathToParam and then get it from product
     * @param $pathToParam config path like module/general/param
     * @param $productId
     * @return mixed attribute value
     */
    public function getAttrValueByParam($pathToParam, $productId)
    {
        $attributeCode = $this->_helper->getConfig($pathToParam);
        if (!$attributeCode || '0' == $attributeCode || 0 === $attributeCode) {
            return $this->_helper->getConfig($pathToParam . '_default');
        }

        return $this->_helper->getAttributeValue($attributeCode, $productId);
    }

    /**
     * Calculation of total dimensions of all goods
     * @param array $dimensions
     * @return array
     */
    public function dimenAlgo(array $dimensions)
    {
        $dim = [];
        $result = [
            'width' => 0,
            'height' => 0,
            'length' => 0,
        ];
        foreach ($dimensions as $d) {
            if ($this->isValidDimensionArr($d)) {
                $dim[] = $d;
            }
        }

        foreach ($dim as $d) {
            ($d['width'] > $result['width']) ? $result['width'] = $d['width'] : '';
            ($d['height'] > $result['height']) ? $result['height'] = $d['height'] : '';
            $result['length'] += $d['length'];
        }
        $result['volume'] = $result['length'] * $result['height'] * $result['width'];

        return $result;
    }

    /**
     * Validate dimmensions value
     * @param array $arr
     * @return boolean
     */
    public function isValidDimensionArr($arr)
    {
        if (!is_array($arr)
            || !array_key_exists('width', $arr)
            || !array_key_exists('height', $arr)
            || !array_key_exists('length', $arr)) {
            return false;
        }

        foreach ($arr as $a) {
            if ((!is_int($a) && !is_float($a))) {
                return false;
            }
        }

        return true;
    }
}
