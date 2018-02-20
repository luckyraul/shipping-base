<?php
/**
 * @author Mygento
 * @copyright 2017 Mygento (https://www.mygento.ru)
 * @package Mygento_Shipment
 */

namespace Mygento\Shipment\Plugin;

class GetRates
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundImportShippingRate(
        \Magento\Quote\Model\Quote\Address\Rate $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Address\RateResult\AbstractResult $rate
    ) {
        $result = $proceed($rate);

        $result->setEstimate($rate->getEstimate());
        $result->setLatitude($rate->getLatitude());
        $result->setLongitude($rate->getLongitude());

        return $result;
    }
}
