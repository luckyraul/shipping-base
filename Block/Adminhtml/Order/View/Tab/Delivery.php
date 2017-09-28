<?php
/**
 * @author Mygento Team
 * @copyright 2017 Mygento (https://www.mygento.ru)
 * @package Mygento_Shipment
 */

namespace Mygento\Shipment\Block\Adminhtml\Order\View\Tab;

class Delivery extends \Magento\Backend\Block\Widget\Tab
{
    protected $_code = 'shipment';
    protected $_coreRegistry;
    protected $_helper;

    public function __construct(
        \Mygento\Shipment\Helper\Data $helper,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_coreRegistry = $registry;
        $this->_urlBuilder = $context->getUrlBuilder();

        parent::__construct($context, $data);
    }

    public function canShowTab()
	{
        return $this->_helper->isShippedBy($this->getOrder());
    }

    public function getTabLabel()
    {
        return __($this->_code . '.tab.shipping');
    }

    public function getTabTitle()
    {
        return __($this->_code . '.tab.shipping');
    }

    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    public function isHidden()
    {
        return false;
    }

    public function getTabClass()
    {
        return 'ajax only';
    }

    public function getClass()
    {
        return $this->getTabClass();
    }

    public function getTabUrl()
    {
        return $this->getUrl('mygento_' . $this->_code . '/*/deliverytab', ['_current' => true]);
    }

    public function getLink($action)
    {
        return $this->_urlBuilder->getUrl(
            'mygento_' . $this->_code . '/delivery/' . $action,
            ['_secure' => true, 'order_id' => $this->getOrder()->getId()]
        );
    }
}
