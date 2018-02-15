<?php
/**
 * @author Mygento
 * @copyright 2017 Mygento (https://www.mygento.ru)
 * @package Mygento_Shipment
 */

namespace Mygento\Shipment\Model;

abstract class AbstractShipment
{
    protected $_code = 'shipment';
    protected $_helper;
    protected $_shipmentFactory;
    protected $_trackFactory;
    protected $_track;
    protected $_shipmentApi;
    protected $shipmentSender;
    protected $sendShipmentEmail = true;

    public function __construct(
        \Mygento\Shipment\Helper\Data $helper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
        \Magento\Sales\Api\Data\ShipmentInterface $shipmentApi,
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Sales\Model\Order\Email\Sender\ShipmentSender $shipSender
    ) {
        $this->_helper = $helper;
        $this->_orderFactory = $orderFactory;
        $this->_shipmentFactory = $shipmentFactory;
        $this->_trackFactory = $trackFactory;
        $this->_shipmentApi = $shipmentApi;
        $this->_eventManager = $eventManager;
        $this->shipmentSender = $shipSender;
    }

    /**
     * Получение заказа по Id
     *
     * @param int $orderId
     * @return object
     */
    public function getOrder($orderId)
    {
        $order = $this->_orderFactory->create()->load($orderId);
        return $order;
    }

    /**
     * Получение заказа по IncrementId
     *
     * @param int $orderId
     * @return object
     */
    public function getOrderByIncrementId($orderIncrementId)
    {
        $order = $this->_orderFactory->create()->loadByIncrementId($orderIncrementId);
        return $order;
    }

    /**
     * Добавление кода отслеживания
     *
     * @param int $orderId
     * @param mixed $orderCode
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function setTracking($orderId, $orderCode)
    {
        //Получение заказа
        if (!$orderId) {
            return $this->error(__('order_ns'));
        }
        $order = $this->getOrder($orderId);
        if (!$order) {
            return $this->error(__('order_nf'));
        }

        $shipping = $order->getShippingMethod(true);

        //Сохранение кода для созданной доставки
        if ($order->getShipmentsCollection()->count() > 0) {
            $shipment = $order->getShipmentsCollection()->getFirstItem();
            if (count($shipment->getAllTracks()) == 0) {
                $data = [
                    'carrier_code' => $shipping->getCarrierCode(),
                    'title' => $order->getShippingDescription(),
                    'number' => $orderCode
                ];

                $shipment->addTrack(
                    $this->_trackFactory->create()->addData($data)
                )->save();
            }
            return $this->success();
        }

        //Создание новой доставки
        if ($order->canShip()) {
            $data = [
                'carrier_code' => $shipping->getCarrierCode(),
                'title' => $order->getShippingDescription(),
                'number' => $orderCode
            ];

            $items = [];
            foreach ($order->getAllItems() as $item) {
                if (! $item->getQtyToShip() || $item->getIsVirtual()) {
                    continue;
                }
                $items[$item->getId()] = [
                    'order_item_id' => $item->getId(),
                    'qty' => $item->getQtyToShip()
                ];
            }

            $shipment = $this->_shipmentFactory->create($order, $items, [$data]);
            if ($shipment) {
                $shipment->register();
                $order->setCustomerNoteNotify($this->sendShipmentEmail);
                $shipment->addComment(__('order shipped by %1', $this->_code));
                $shipment->getOrder()->setIsInProcess(true);
                $shipment->save();
                $shipment->getOrder()->save();
                if ($this->sendShipmentEmail) {
                    $this->shipmentSender->send($shipment);
                }
            }
            return $this->success();
        }
    }

    //Получение кода отслеживания
    public function getTracking($orderId)
    {
        //Получение заказа
        if (!$orderId) {
            return $this->error(__('order_ns'));
        }
        $order = $this->getOrder($orderId);
        if (!$order) {
            return $this->error(__('order_nf'));
        }

        //Получение доставки
        if ($order->getShipmentsCollection()->count() == 0) {
            return $this->error(__('shipment_nf'));
        }
        $shipment = $order->getShipmentsCollection()->getFirstItem();
        $tracks = $shipment->getAllTracks();

        //Получение кода отслеживания
        if (count($tracks) == 0) {
            return $this->error(__('shipment_track_nf'));
        }
        $track = $tracks[0];
        if ($this->_code != $track->getData('carrier_code')) {
            return $this->error(__('wrong_shipment_track'));
        }

        return $this->success('', ['code' => $track->getNumber()]);
    }

    //Сброс заказа
    public function orderReset($orderId)
    {
        //Получение заказа
        if (!$orderId) {
            return $this->error(__('order_ns'));
        }
        $order = $this->getOrder($orderId);
        if (!$order) {
            return $this->error(__('order_nf'));
        }

        //Удаление доставки
        $shipments = $order->getShipmentsCollection();
        foreach ($shipments as $shipment) {
            $shipment->delete();
        }

        //Очистка доставленных товаров
        $items = $order->getAllVisibleItems();
        foreach ($items as $i) {
            $i->setQtyShipped(0);
            $i->save();
        }

        //Сброс статуса
        $order->setState(\Magento\Sales\Model\Order::STATE_NEW, true);
        $order->save();

        return $this->success('', ['reload' => true]);
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

    /**
     * @param bool $sendShipmentEmail
     */
    public function setSendShipmentEmail($sendShipmentEmail): void
    {
        $this->sendShipmentEmail = (bool)$sendShipmentEmail;
    }
}
