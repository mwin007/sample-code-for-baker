<?php
namespace Sooryen\Auth0\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Exception\NoSuchEntityException;


use Magento\Framework\UrlInterface;

class CustomerLogOut implements ObserverInterface
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        UrlInterface $url, 
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Sooryen\Auth0\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->messageManager = $context->getMessageManager();
        $this->_logger = $logger;
        $this->url = $url;
        $this->_responseFactory = $responseFactory;
        $this->helper           = $helper;
    }
    
    public function execute(EventObserver $observer)
    {
        try 
        {
            $authCred   =   $this->helper->auth0Enabled();
            // If auth0 not enabled or some configuration is missing 
            if(!$authCred)
            {
                return true;
            }
            $this->helper->logout();
        }
        
        catch (\Exception $e) 
        {
            $redirect_uri = $this->url->getUrl('customer/account/logoutSuccess');
            $this->messageManager->addError( __('Invalid Logout '.$e->getMessage()) );
            $this->_responseFactory->create()->setRedirect($redirect_uri)->sendResponse();
            exit();
        }
    }
}