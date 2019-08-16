<?php
namespace Sooryen\Auth0\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Exception\NoSuchEntityException;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\UrlInterface;
use \Sooryen\Auth0\Model\ResourceModel\Auth0\CollectionFactory as Auth0CollectionFactory;


class CustomerDeleteBefore implements ObserverInterface
{
    
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        UrlInterface $url, 
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Sooryen\Auth0\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Sooryen\Auth0\Model\Auth0Factory $auth0Factory,
        Auth0CollectionFactory $auth0CollectionFactory
            
    )
    {
        $this->scopeConfig      = $scopeConfig;
        $this->messageManager   = $context->getMessageManager();
        $this->url              = $url;
        $this->_responseFactory = $responseFactory;
        $this->helper           = $helper;
        $this->jsonHelper       = $jsonHelper;
        $this->_logger          = $logger;
        $this->auth0Factory = $auth0Factory;
        $this->auth0CollectionFactory = $auth0CollectionFactory;
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
            else
            {
                $customer=$observer->getEvent()->getCustomer();
                if($customer)
                {
                    $customerEmail      =   $customer->getEmail();
                    $auth0Collection    = $this->auth0CollectionFactory->create();
                    $cutsomerAuth       = $auth0Collection->addFieldToFilter('customer_email',$customerEmail)->load();
                    if($cutsomerAuth)
                    {
                        $auth=$cutsomerAuth->getFirstItem();
                        $authId = $auth->getId();
                        $authmodel = $this->auth0Factory->create();
                        $authmodel->load($authId);
                        $authmodel->delete(); /* Remove customer data from sooryen_auth0 table to avoid unique contratint error*/
                    }
                }
            }
        }
        catch (\Exception $e) 
        {
            $this->messageManager->addError( __('Error in customer removal '.$e->getMessage()) );
        }
    }
}