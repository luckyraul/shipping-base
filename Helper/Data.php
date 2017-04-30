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
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
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
    public function morthDays($dayCount)
    {
        $form1 = 'день';
        $form2 = 'дня';
        $form5 = 'дней';
        $dayCount = abs($dayCount) % 100;
        $n1 = $dayCount % 10;
        if ($dayCount > 10 && $dayCount < 20) {
            return $form5;
        }
        if ($n1 > 1 && $n1 < 5) {
            return $form2;
        }
        if ($n1 == 1) {
            return $form1;
        }
        return $form5;
    }

    /**
     *
     * @param integer $dayCount
     * @return boolean
     */
    public function clearDb($model_name)
    {
        //see ponyexpress
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
            'W' => 0,
            'H' => 0,
            'L' => 0,
        ];
        foreach ($dimensions as $d) {
            if ($this->isValidDimensionArr($d)) {
                rsort($d);
                $dim[] = $d;
            }
        }

        foreach ($dim as $d) {
            ($d[0] > $result['W']) ? $result['W'] = $d[0] : '';
            ($d[1] > $result['H']) ? $result['H'] = $d[1] : '';
            $result['L'] += $d[2];
        }

        $this->addLog('Array after dimension sorting');
        $this->addLog(print_r($result, true));

        return $result;
    }

    private function isValidDimensionArr($arr)
    {
        if (is_array($arr) and 3 == sizeof($arr)) {
            foreach ($arr as $a) {
                if ((!is_int($a) and !is_float($a)) or $a < 0.1) {
                    return false;
                }
            }
        } else {
            return false;
        }
        return true;
    }
}
