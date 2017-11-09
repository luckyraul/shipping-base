<?php
/**
 * @author Mygento
 * @copyright 2017 Mygento (https://www.mygento.ru)
 * @package Mygento_Shipment
 */

namespace Mygento\Shipment\Helper;

/**
 *
 * Shipment Data helper
 *
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends \Mygento\Base\Helper\Data
{
    protected $_code = 'shipment';
    protected $_tempProduct = null;
    protected $_checkoutSession;
    protected $_invoiceService;
    protected $_transaction;
    protected $_storeManager;
    protected $_templatePrefix = ['{{', '}}'];

    /**
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Mygento\Base\Model\Logger\LoggerFactory $loggerFactory
     * @param \Mygento\Base\Model\Logger\HandlerFactory $handlerFactory
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     *
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Mygento\Base\Model\Logger\LoggerFactory $loggerFactory,
        \Mygento\Base\Model\Logger\HandlerFactory $handlerFactory,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ProductRepository $productRepository
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_storeManager = $storeManager;

        parent::__construct(
            $context,
            $loggerFactory,
            $handlerFactory,
            $encryptor,
            $curl,
            $productRepository
        );
    }

    /**
     *
     * @return string
     */
    public function getCode()
    {
        return $this->_code;
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
        $quote = $this->_checkoutSession->getQuote();
        return $quote;
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
            $invoice = $this->_invoiceService->prepareInvoice($order);
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

    /**
     *
     * @param integer $dayCount
     * @return boolean
     */
    public function monthDays($dayCount)
    {
        $form1 = 'день';
        $form2 = 'дня';
        $form5 = 'дней';
        $dayCount = abs($dayCount) % 100;
        $num = $dayCount % 10;
        if ($dayCount > 10 && $dayCount < 20) {
            return $form5;
        }
        if ($num > 1 && $num < 5) {
            return $form2;
        }
        if ($num == 1) {
            return $form1;
        }
        return $form5;
    }

    /**
     * Clearing DB table
     */
    public function clearDb($resourceModel)
    {
        $connection = $resourceModel->getConnection();
        $tableName = $resourceModel->getMainTable();
        $connection->truncateTable($tableName);
    }

    /**
     *
     * @param type $configPath
     * @return type
     */
    public function getConfig($path)
    {
        return parent::getConfig('carriers/' . $this->_code . '/' . $path);
    }

    protected function getDebugConfigPath()
    {
        return 'debug';
    }

    /**
     * Templating
     * @param string $tpl
     * @param array $data
     * @return string
     */
    public function dataTemplate($tpl, $data)
    {
        $keys = array_keys($data);
        array_walk($keys, function (& $value) {
            $value = $this->_templatePrefix[0] . strtoupper($value) . $this->_templatePrefix[1];
        });

        $output = str_replace($keys, $data, $tpl);
        return $output;
    }

    /**
     * Getting store title
     * @return string
     */
    public function getStoreName()
    {
        return $this->_storeManager->getStore()->getName();
    }
}
