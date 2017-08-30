<?php
/**
 * @author Mygento Team
 * @copyright 2017 Mygento (https://www.mygento.ru)
 * @package Mygento_Shipment
 */

namespace Mygento\Shipment\Controller\Adminhtml\Delivery;

abstract class AbstractAction extends \Magento\Backend\App\Action
{
    protected $_fileFactory;
    protected $_resultRawFactory;
    protected $_resultJsonFactory;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->_fileFactory = $fileFactory;
        $this->_resultRawFactory = $resultRawFactory;
        $this->_resultJsonFactory = $resultJsonFactory;
        
        parent::__construct($context);
    }
}
