<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Shipment
 */
namespace Mygento\Shipment\Model;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;

class AbstractMethod extends AbstractCarrier implements CarrierInterface
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
     * @param \Mygento\Shipment\Helper\Data $helper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        \Mygento\Shipment\Helper\Data $helper,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {
    
        $this->_helper            = $helper;
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     *
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @return \Magento\Shipping\Model\Rate\ResultFactory $result
     */
    public function collectRates(RateRequest $request)
    {
        \Magento\Framework\Profiler::start($this->_code . '_collect_rate');

        $valid = $this->validateRequest($request);

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
    private function validateRequest(RateRequest $request)
    {
        if (!$this->getConfigData('active')) {
            return false;
        }

        $this->_helper->addLog('Started calculating');

        if (strlen($request->getDestCity()) <= 2) {
            $this->_helper->addLog('City strlen <= 2, aborting ...');
            return false;
        }

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
        $quote    = $this->_helper->getCurrentQuote();
        $totals   = $quote->getTotals();
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
        $this->_helper->addLog($message);

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
    public function isCityRequired()
    {
        return true;
    }
}
