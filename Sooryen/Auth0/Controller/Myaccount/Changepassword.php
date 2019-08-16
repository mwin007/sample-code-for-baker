<?php 
namespace Sooryen\Auth0\Controller\Myaccount;
use Magento\Framework\UrlInterface;
use Magento\Customer\Model\Session;

class Changepassword extends \Magento\Framework\App\Action\Action
{
    const AUTH0_ENABLE        =    'sooryen_auth0/general/enable';
    const AUTH0_DOMAIN       =    'sooryen_auth0/general/auth0_domian';
    const AUTH0_CLIENTID      =    'sooryen_auth0/general/auth0_clientid';
    const AUTH0_CLIENT_SECRET =    'sooryen_auth0/general/auth0_client_secret';
    const AUTH0_DB           =    'sooryen_auth0/general/auth0_db_connection';
    
    /**
     * @var Session
     */
    protected $session;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        UrlInterface $url,
        \Sooryen\Auth0\Helper\Data $helper,
        Session $customerSession,
        \Magento\Framework\App\ResponseFactory $responseFactory
    )
    {
	$this->_pageFactory = $pageFactory;
        $this->scopeConfig = $scopeConfig;
        $this->url = $url;
        $this->helper           = $helper;
        $this->customersession = $customerSession;
        $this->_responseFactory = $responseFactory;
        return parent::__construct($context);
    }
    
    public function execute()
    {
        try 
        {
            if ($this->customersession->isLoggedIn()) 
            {
                $userEmailId =$this->customersession->getCustomer()->getEmail();
                
                $authCred   =   $this->helper->getAuth0_credentials();
                // If auth0 not enabled or some configuration is missing 
                if(!$authCred)
                {
                    return true;
                }
                $auth0_changepassUrl        = $authCred['domain'].'dbconnections/change_password';
                $requestField = array(  'client_id'=>$authCred['clientId'],
                                    'connection'=>$authCred['db'],
                                    'email' => $userEmailId
                            );
                $requestParameter= http_build_query($requestField);
                $curl = curl_init();
                curl_setopt_array($curl, array( CURLOPT_URL => $auth0_changepassUrl."?".$requestParameter,
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_CUSTOMREQUEST => "GET",
                                CURLOPT_HTTPHEADER => array(    "content-type: application/json" ),
                        )
                    );
                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);
                $customRedirectionUrl   =   $this->url->getUrl('customer/account');
                $this->messageManager->addSuccess( __( $response) );
            }
            $this->_responseFactory->create()->setRedirect($customRedirectionUrl)->sendResponse();
            exit();
        }
        catch (\Exception $e) 
        {
            $customRedirectionUrl   =   $this->url->getUrl('customer/account');
            $this->messageManager->addError( __('Invalid request '.$e->getMessage()) );
            $this->_responseFactory->create()->setRedirect($customRedirectionUrl)->sendResponse();
            exit();
        }
    }
}