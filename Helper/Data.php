<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Shipment
 */
namespace Mygento\Shipment\Helper;

/**
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
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Mygento\Base\Model\Logger\LoggerFactory $loggerFactory
     * @param \Mygento\Base\Model\Logger\HandlerFactory $handlerFactory
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
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
    }

    /**
     *
     * @return type
     */
    public function getDbCount()
    {
        return;
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
}
