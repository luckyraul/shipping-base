<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Shipment
 */

namespace Mygento\Shipment\Model;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Catalog\Model\Product as ModelProduct;

class AbstractCarrier extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements CarrierInterface
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
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $_rateResultFactory;
    
    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $_rateMethodFactory;
    
    /**
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    
    /**
     *
     * @param \Mygento\Shipment\Helper\Data $helper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * //TODO заполнить
     * @param array $data
     */
    public function __construct(
        \Mygento\Shipment\Helper\Data $helper,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {
        
        $this->_helper = $helper;
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_checkoutSession = $checkoutSession;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }
    
    /**
     *
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @return $result
     */
    public function collectRates(RateRequest $request)
    {
        \Magento\Framework\Profiler::start($this->_code . '_collect_rate');
        
        $valid = $this->_validateRequest($request);
        
        if ($valid !== true) {
            return $valid;
        }
        
        \Magento\Framework\Profiler::stop($this->_code . '_collect_rate');
        return $this->_rateResultFactory;
    }
    
    /**
     * Validate shipping request before processing
     *
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @return boolean
     */
    protected function _validateRequest(RateRequest $request)
    {
        if (!$this->getConfigData('active')) {
            return false;
        }
        
        $this->_helper->addLog('Started calculating to: ' . $request->getDestCity());
        
        if (strlen($request->getDestCity()) <= 2) {
            $this->_helper->addLog('City strlen <= 2, aborting ...');
            return false;
        }
        
        $this->_helper->addLog('Weight: ' . $request->getPackageWeight());
        
        if (0 >= $request->getPackageWeight()) {
            return $this->returnError('Zero weight');
        }
        
        return true;
    }
    
    /**
     *
     * @return number
     */
    protected function _getCartTotal()
    {
        $quote = $this->_helper->getCurrentQuote();
        $totals = $quote->getTotals();
        $subtotal = $totals['subtotal']->getValue();
        if (isset($totals['discount'])) {
            $subtotal = $subtotal + $totals['discount']->getValue();
        }
        return $subtotal;
    }
    
    /**
     *
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @param string $mode
     * @param string $encoding
     * @return string
     */
    protected function _convertCity($request, $mode = MB_CASE_TITLE, $encoding = 'UTF-8')
    {
        return mb_convert_case(trim($request->getDestCity()), $mode, $encoding);
    }
    
    /**
     *
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @return mixed
     */
    protected function _convertWeight($request)
    {
        return intval($request->getPackageWeight() * $this->getConfigData('weightunit'));
    }
    
    /**
     *
     * @param string $message
     * @return boolean | \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $error
     */
    private function returnError($message)
    {
        //TODO not working
        $this->_helper->addLog('Error message ' . $message);
        
        if ($this->getConfigData('debug')) {
            $error = $this->_rateErrorFactory->create();
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage(__($message));
            return $error;
        }
        return false;
    }
    
    /**
     *
     * @return boolean
     */
    public function isTrackingAvailable()
    {
        return true;
    }
    
    /**
     *
     * @return boolean
     */
    public function getAllowedMethods()
    {
        return [];
    }
    
    /**
     *
     * @return boolean
     */
    public function isCityRequired()
    {
        return true;
    }
}
