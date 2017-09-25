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
        \Magento\Catalog\Model\ProductRepository $productRepository
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;

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
     *
     * @param integer $dayCount
     * @return boolean
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

    /**
     * алгоритм расчета суммарных габаритов всех товаров
     * @param array $dimensions
     * @return array
     */
    public function dimenAlgo(array $dimensions)
    {
        $this->addLog('Array before dimension sorting');
        $this->addLog(print_r($dimensions, true));

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

        $this->addLog('Array after dimension sorting');
        $this->addLog(print_r($result, true));

        return $result;
    }

    private function isValidDimensionArr($arr)
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

    public function getItemsSizes($sizeCoefficient, $weightCoefficient, $object, $prefix = '')
    {

        $resultArray = [];

        if (!$object->getAllVisibleItems()) {
            return $resultArray;
        }

        foreach ($object->getAllVisibleItems() as $item) {
            if ($item->getProduct() instanceof \Magento\Catalog\Model\Product) {
                $qty = $item->getQty();

                if ($object instanceof \Magento\Sales\Model\Order) {
                    $qty = $item->getQtyOrdered();
                }

                for ($i = 1; $i <= $qty; $i++) {
                    $productId = $item->getProductId();

                    $itemArray = [];

                    $itemArray['length'] = round($this->getAttrValueByParam(
                        $prefix . 'length',
                        $productId
                    ) *
                        $sizeCoefficient, 2);
                    $itemArray['height'] = round($this->getAttrValueByParam(
                        $prefix . 'height',
                        $productId
                    ) *
                        $sizeCoefficient, 2);
                    $itemArray['width'] = round($this->getAttrValueByParam(
                        $prefix . 'width',
                        $productId
                    ) *
                        $sizeCoefficient, 2);
                    $itemArray['volume'] = $itemArray['length']
                        * $itemArray['height']
                        * $itemArray['width'];
                    $itemArray['weight'] = round($item->getWeight() * $weightCoefficient, 2);

                    $resultArray[] = $itemArray;
                }
            }
        }

        return $resultArray;
    }

    //Шаблонизация
    public function dataTemplate($tpl, $data)
    {
        $keys = array_keys($data);
        array_walk($keys, function (& $value) {
            $value = $this->_templatePrefix[0] . strtoupper($value) . $this->_templatePrefix[1];
        });

        $output = str_replace($keys, $data, $tpl);
        return $output;
    }

    //Название магазина
    public function getStoreName()
    {
        return $this->_storeManager->getStore()->getName();
    }
}
