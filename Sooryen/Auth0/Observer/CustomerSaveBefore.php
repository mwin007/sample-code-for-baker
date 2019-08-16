<?php
namespace Sooryen\Auth0\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Exception\NoSuchEntityException;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\UrlInterface;



class CustomerSaveBefore implements ObserverInterface
{
    
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        UrlInterface $url, 
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Sooryen\Auth0\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    )
    {
        $this->scopeConfig      = $scopeConfig;
        $this->messageManager   = $context->getMessageManager();
        $this->url              = $url;
        $this->_responseFactory = $responseFactory;
        $this->helper           = $helper;
        $this->jsonHelper       = $jsonHelper;
        $this->_logger          = $logger;
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
                $userInput      =   $observer->getRequest()->getPost();
                $email          =   $userInput['email'];
                $password       =   $userInput['password'];
                $response       =   $this->helper->signup($email,$password);/* Auth0 signup  */
                $response       =   $this->jsonHelper->jsonDecode($response);
                if($response['status'])
                {
                    $customRedirectionUrl = $this->url->getUrl('customer/account/create/');
                    $this->messageManager->addSuccess( __($response['message']) );
                    $this->_responseFactory->create()->setRedirect($customRedirectionUrl)->sendResponse();
                     // Uncomment above line to prevent user signup in magento hence customer will not save in  magento //
                    //$mageSignUrl = $this->url->getUrl('customer/account/create/');
                    //$this->_responseFactory->create()->setRedirect($mageSignUrl)->sendResponse();
                    // exit();
                    //return ;
                }
                else
                {
                    //If auth0 signup fail //
                    $mageSignUrl = $this->url->getUrl('customer/account/create/');
                    $this->messageManager->addError( __($response['message']) );
                    $this->_responseFactory->create()->setRedirect($mageSignUrl)->sendResponse();
                    exit();
                }
            }
        }
        catch (\Exception $e) 
        {
            $customRedirectionUrl   =   $this->url->getUrl('customer/account/create/');
            $this->messageManager->addError( __('Invalid signup '.$e->getMessage()) );
            $this->_responseFactory->create()->setRedirect($customRedirectionUrl)->sendResponse();
            exit();
        }
    }
}