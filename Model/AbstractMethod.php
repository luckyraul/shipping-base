<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Shipment
 */
namespace Mygento\Shipment\Model;

/**
 * Shipment Data helper
 */
class AbstractMethod
{

    /**
     *
     * @var string
     */
    protected $_code = 'shipment';

    /**
     *
     * @var \Mygento\Shipment\Helper\Data
     */
    protected $_helper;

    /**
     *
     * @var \Magento\Sales\Model\Order\ShipmentFactory
     */
    protected $_shipmentFactory;

    /**
     *
     * @var \Magento\Sales\Model\Order\Shipment\TrackFactory
     */
    protected $_trackFactory;

    /**
     *
     * @var \Magento\Sales\Model\Order\Shipment\Track
     */
    protected $_track;

    /**
     *
     * @var \Magento\Sales\Api\Data\ShipmentInterface
     */
    protected $_shipmentApi;

    /**
     *
     * @param \Mygento\Shipment\Helper\Data $helper
     * @param \Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory
     * @param \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory
     * @param \Magento\Sales\Api\Data\ShipmentInterface $shipmentApi
     */
    public function __construct(
        \Mygento\Shipment\Helper\Data $helper,
        \Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
        \Magento\Sales\Api\Data\ShipmentInterface $shipmentApi
    ) {
    
        $this->_helper          = $helper;
        $this->_shipmentFactory = $shipmentFactory;
        $this->_trackFactory    = $trackFactory;
        $this->_shipmentApi     = $shipmentApi;
    }

    /**
     *
     * @param \Magento\Sales\Model\Order $order
     * @return mixed
     */
    protected function _getTracking(\Magento\Sales\Model\Order $order)
    {
        if (0 == $order->getShipmentsCollection()->count()) {
            $this->_helper->addLog('no shipment found for print');
            return ['error' => true, 'message' => 'no shipment found for print'];
        }

        $shipment = $order->getShipmentsCollection()->getFirstItem();
        $tracks   = $shipment->getAllTracks();

        if (0 == count($tracks)) {
            $this->_helper->addLog('no shipment track found for print');
            return ['error' => true, 'message' => 'no shipment track found for print'];
        }

        $track = $tracks[0];

        if ($this->_code != $track->getData('carrier_code')) {
            $this->_helper->addLog('wrong shipment track found for print');
            return ['error' => true, 'message' => 'wrong shipment track found for print'];
        }

        return $track->getNumber();
    }

    /**
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string $code
     */
    protected function _setTracking(\Magento\Sales\Model\Order $order, $code)
    {
        $shipping = $order->getShippingMethod(true);

        if ($order->getShipmentsCollection()->count() > 0) {
            $shipment = $order->getShipmentsCollection()->getFirstItem();
            if (count($shipment->getAllTracks()) == 0) {
                $data = [
                    $shipment->getIncrementId(),
                    $shipping->getCarrierCode(),
                    $order->getShippingDescription(),
                    $code
                ];

                $this->_shipmentApi->addTrack(
                    $this->_trackFactory->create()->addData($data)
                );
            }
            return;
        }

        if ($order->canShip()) {
            $shipment = $this->_shipmentFactory->create($order, [], $code);

            if ($shipment) {
                $shipment->register();
                $shipment->addComment($this->_helper->__('order shipped by %1', $this->_code));
                $shipment->getOrder()->setIsInProcess(true);

                $track = $this->_trackFactory->create()
                    ->setNumber($code)
                    ->setCarrierCode($shipping->getCarrierCode())
                    ->setTitle($order->getShippingDescription());

                $shipment->addTrack($track);
                $shipment->save();
                $shipment->getOrder()->save();
            }
        }
    }
}
