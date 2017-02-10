<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Shipment
 */
namespace Mygento\Shipment\Helper;

/**
 * 
 * Shipment Data helper
 */
class Data extends \Mygento\Base\Helper\Data
{

    /**
     *
     * @var string
     */
    protected $_code = 'shipment';

    /**
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     *
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $_invoiceService;

    /**
     *
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;

    /**
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Mygento\Base\Model\Logger\LoggerFactory $loggerFactory
     * @param \Mygento\Base\Model\Logger\HandlerFactory $handlerFactory
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Framework\App\Helper\Context $context,
        \Mygento\Base\Model\Logger\LoggerFactory $loggerFactory,
        \Mygento\Base\Model\Logger\HandlerFactory $handlerFactory,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {

        parent::__construct(
            $context,
            $loggerFactory,
            $handlerFactory,
            $encryptor,
            $curl
        );

        $this->_checkoutSession = $checkoutSession;
        $this->_invoiceService  = $invoiceService;
        $this->_transaction     = $transaction;
    }

    /**
     *
     * @param \Magento\Framework\Data\Collection $collection
     * @return mixed
     */
    public function getCount($collection)
    {
        return $collection ? $collection->getSize() : 0;
    }

    /**
     *
     * @param \Magento\Sales\Model\Order $order
     * @return boolean
     */
    public function isShippedBy(\Magento\Sales\Model\Order $order)
    {
        if (strpos($order->getShippingMethod(), $this->_code . '_') !== false) {
            return true;
        }
        return false;
    }

    /**
     *
     * @param \Magento\Sales\Model\Order $order
     * @return boolean
     */
    public function hasTrack(\Magento\Sales\Model\Order $order)
    {
        foreach ($order->getShipmentsCollection() as $shipment) {
            foreach ($shipment->getAllTracks() as $tracknum) {
                return $tracknum->getNumber();
            }
        }
        return false;
    }

    /**
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getCurrentQuote()
    {
        return $this->_checkoutSession->getQuote();
    }

    /**
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     */
    public function invoiceShipment(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        $itemsarray = [];

        $shippedItems = $shipment->getItemsCollection();
        foreach ($shippedItems as $item) {
            $itemsarray[$item->getOrderItemId()] = $item->getQty();
        }

        $order = $shipment->getOrder();

        if ($order->canInvoice()) {
            $invoice         = $this->_invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->save();
            $transactionSave = $this->_transaction
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transactionSave->save();

            $this->addLog(
                'Order #' . $order->getIncrementId() . ' Invoiced: #' . $invoice->getId()
            );
        }
    }
}
