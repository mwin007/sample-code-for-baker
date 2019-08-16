<?php 
namespace Sooryen\Auth0\Controller\Login;
use Magento\Framework\UrlInterface;
class Index extends \Magento\Framework\App\Action\Action
{
    const AUTH0_ENABLE        =    'sooryen_auth0/general/enable';
    const AUTH0_DOMAIN       =    'sooryen_auth0/general/auth0_domian';
    const AUTH0_CLIENTID      =    'sooryen_auth0/general/auth0_clientid';
    const AUTH0_CLIENT_SECRET =    'sooryen_auth0/general/auth0_client_secret';
    const AUTH0_DB           =    'sooryen_auth0/general/auth0_db_connection';
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        UrlInterface $url,
        \Sooryen\Auth0\Helper\Data $helper
    )
    {
	$this->_pageFactory = $pageFactory;
        $this->scopeConfig = $scopeConfig;
        $this->url = $url;
        $this->helper           = $helper;
        return parent::__construct($context);
    }
    
    public function execute()
    {
        try 
        {
            $authCred   =   $this->helper->getAuth0_credentials();
            // If auth0 not enabled or some configuration is missing 
            if(!$authCred)
            {
                return true;
            }
           
            $redirect_uri           = $this->url->getUrl('auth0/login/authorize/');
            $auth0_login_url        = $authCred['domain'].'authorize';
        
        
            $requestField = array(  'client_id'=>$authCred['clientId'],
                                'response_type'=>'code',
                                'connection'=>$authCred['db'],
                                'state'=>'STATE',
                                'redirect_uri'=> $redirect_uri,
                                'scope'=>'openid profile email'
                            );
            $requestParameter= http_build_query($requestField);
            $curl = curl_init();
            curl_setopt_array($curl, array( CURLOPT_URL => $auth0_login_url."?".$requestParameter,
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => "",
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 30,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => "GET",
                                CURLOPT_FOLLOWLOCATION =>true,
                                CURLOPT_HTTPHEADER => array(    "content-type: application/json" ),
                )
            );
            $redirectURL = curl_getinfo($curl,CURLINFO_EFFECTIVE_URL );
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            
        }
        catch (\Exception $e) 
        {
            $customRedirectionUrl   =   $this->url->getUrl('customer/account/create/');
            $this->messageManager->addError( __('Invalid signup '.$e->getMessage()) );
            $this->_responseFactory->create()->setRedirect($customRedirectionUrl)->sendResponse();
            exit();
        }
        
        if ($err) 
        {
            echo "cURL Error #:" . $err;
        } 
        else 
        {
            echo $response;die;
        }
    }
}