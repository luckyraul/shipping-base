<?php
/**
 * @author Mygento
 * @copyright 2017 Mygento (https://www.mygento.ru)
 * @package Mygento_Shipment
 */

namespace Mygento\Shipment\Model;

abstract class AbstractShipment
{
    public $_code = 'shipment';
    public $_helper;
    public $_shipmentFactory;
    public $_trackFactory;
    public $_track;
    public $_shipmentApi;
    
    public function __construct(
        \Mygento\Shipment\Helper\Data $helper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
        \Magento\Sales\Api\Data\ShipmentInterface $shipmentApi
    ) {
        $this->_helper = $helper;
        $this->_orderFactory = $orderFactory;
        $this->_shipmentFactory = $shipmentFactory;
        $this->_trackFactory = $trackFactory;
        $this->_shipmentApi = $shipmentApi;
    }
    
    //Запрос
    abstract public function request($method, $data = []);
    
    //Получение методов доставки
    abstract public function getDeliveriesMethods($data);
    
    //Получение заказа
    public function getOrder($orderId)
    {
        $order = $this->_orderFactory->create()->load($orderId);
        return $order;
    }
    
    //Добавление кода отслеживания
    public function setTracking($orderId, $orderCode)
    {
        if (!$orderId) {
            return $this->error(__('No order ID set'));
        }
        
        $order = $this->getOrder($orderId);
        if (!$order) {
            return $this->error(__('No order set'));
        }
        
        $shipping = $order->getShippingMethod(true);
        
        /**** !!!!!!! RESET ORDER TMP !!!!!!! ****/
        //delete shipment
        /*$shipments = $order->getShipmentsCollection();
        foreach ($shipments as $shipment){
            $shipment->delete();
        }
        // Reset item shipment qty
        // see Mage_Sales_Model_Order_Item::getSimpleQtyToShip()
        $items = $order->getAllVisibleItems();
        foreach($items as $i){
           $i->setQtyShipped(0);
           $i->save();
        }
        //Reset order state
        $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, true);
        $order->save();
        return $this->success('Reset');*/
        /**** !!!!!!! END !!!!!!! ****/
        
        /*if ($order->getShipmentsCollection()->count() > 0) {
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
        }*/
        
        if ($order->canShip()) {
            $data = [
                'carrier_code' => $shipping->getCarrierCode(),
                'title' => $order->getShippingDescription(),
                'number' => $orderCode
            ];
            
            $shipment = $this->_shipmentFactory->create($order, [], [$data]);
            if ($shipment) {
                $shipment->register();
                $shipment->addComment(__('order shipped by %1', $this->_code));
                $shipment->getOrder()->setIsInProcess(true);
                $shipment->save();
                $shipment->getOrder()->save();
            }
            return $this->success();
        }
    }
    
    //Получение кода отслеживания
    public function getTracking($orderId)
    {
        if (!$orderId) {
            return $this->error(__('No order ID set'));
        }
        
        $order = $this->getOrder($orderId);
        if (!$order) {
            return $this->error(__('No order set'));
        }
        
        if ($order->getShipmentsCollection()->count() == 0) {
            return $this->error(__('no shipment found for print'));
        }
        
        $shipment = $order->getShipmentsCollection()->getFirstItem();
        $tracks = $shipment->getAllTracks();
        
        if (count($tracks) == 0) {
            return $this->error(__('no shipment track found for print'));
        }
        
        $track = $tracks[0];
        
        if ($this->_code != $track->getData('carrier_code')) {
            return $this->error(__('wrong shipment track found for print'));
        }
        
        return $this->success('', ['code' => $track->getNumber()]);
    }
    
    //Возврат успешного статуса
    public function success($message = '', $data = [])
    {
        $output = array_merge([
            'success' => 1,
            'message' => $message
        ], $data);
        return $output;
    }
    
    //Возврат ошибочного статуса
    public function error($message = '', $data = [])
    {
        $output = array_merge([
            'success' => 0,
            'message' => $message
        ], $data);
        return $output;
    }
}
