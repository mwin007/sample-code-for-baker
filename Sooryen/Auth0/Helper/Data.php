<?php
namespace Sooryen\Auth0\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\Controller\ResultFactory;

class Data extends AbstractHelper 
{
    const AUTH0_ENABLE         =    'sooryen_auth0/general/enable';
    const AUTH0_DOMAIN         =    'sooryen_auth0/general/auth0_domian';
    const AUTH0_CLIENTID        =    'sooryen_auth0/general/auth0_clientid';
    const AUTH0_CLIENT_SECRET   =    'sooryen_auth0/general/auth0_client_secret';
    const AUTH0_DB             =    'sooryen_auth0/general/auth0_db_connection';
    
    public function __construct(\Magento\Framework\App\Action\Context $context,
                                \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
                                \Magento\Customer\Api\CustomerRepositoryInterface $customerRepo,
                                Registry $registry, UrlInterface $url, ResultFactory $resultFactory,
                                \Magento\Framework\App\ResponseFactory $responseFactory,
                                \Sooryen\Auth0\Model\Auth0Factory $auth0Factory,
                                \Magento\Framework\Json\Helper\Data $jsonHelper,
                                \Psr\Log\LoggerInterface $logger
                            )
    {
        $this->scopeConfig = $scopeConfig;
        $this->messageManager = $context->getMessageManager();
        $this->_logger = $logger;
        $this->url = $url;
        $this->resultFactory = $resultFactory;
        $this->_responseFactory = $responseFactory;
        $this->auth0Factory = $auth0Factory;
        $this->jsonHelper = $jsonHelper;
    }
    
    public function signup($emailId , $password)
    {
        $authCred = $this->getAuth0_credentials();
        $return = false;
        $return['status'] = false;
        if($authCred)
        {
            $customRedirectionUrl   =   $this->url->getUrl('customer/account/create/');
            $auth0_domain           =   $authCred['domain'];
            //$username             =   time(); // Enable this if username is required for auth0 //
            $auth0_signup_url       =   $auth0_domain.'dbconnections/signup';
            
            $postField = array( 'client_id'=>    $authCred['clientId'],
                                'email'    =>    $emailId,
                                'password' =>    $password,
                                'connection'=>   $authCred['db']
                                //'username'  =>   $username
                                );
            $postField=$this->jsonHelper->jsonEncode($postField);
            $curl = curl_init();
            curl_setopt_array($curl, array( CURLOPT_URL => $auth0_signup_url,
                                            CURLOPT_RETURNTRANSFER => true,
                                            CURLOPT_ENCODING => "",
                                            CURLOPT_MAXREDIRS => 10,
                                            CURLOPT_TIMEOUT => 30,
                                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                            CURLOPT_CUSTOMREQUEST => "POST",
                                            CURLOPT_POSTFIELDS => $postField,
                                            CURLOPT_HTTPHEADER => array(
                                                "content-type: application/json"
                                            ),
                ));
            $response = curl_exec($curl);
            $response = json_decode($response);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) 
            {
                $return['status'] = false;
                $return['message'] = $err;
            } 
            elseif(isset($response->statusCode) && $response->statusCode==400)
            {
                $return['status'] = false;
                $return['message'] = $response->description;
            }
            else
            {
                $auth0Model = $this->auth0Factory->create();
                $data['auth0_id'] = $response->_id; 
                $data['customer_email'] = $response->email; 
                $data['created_at'] = new \DateTime(); 
                $data['updated_at'] = new \DateTime(); 
                $auth0Model->setData($data);
                $auth0Model->save();
                if(isset($response->email_verified) && !$response->email_verified)
                {
                    $return['status'] = true;
                    $return['message'] = 'You must confirm your account. Please check your email for the cofirmation link.';
                    /* Uncomment above line to prevent user signup in magento hence customer will not save in  magento */
                    //exit();
                }
            }
        }   
        return $this->jsonHelper->jsonEncode($return);
        
    }
    
    public function signin()
    {
        
    }
    
    public function logout()
    {
        $authCred = $this->getAuth0_credentials();
        if($authCred)
        {
            $auth0_domain           =   $authCred['domain'];
            $auth0_logout_url    = $auth0_domain.'v2/logout';
            $redirect_uri = $this->url->getUrl('customer/account/logoutSuccess');
            $requestField = array(  'client_id'=>$authCred['clientId'],
                                        'returnTo'=> $redirect_uri 
                                    );
            $requestParameter= http_build_query($requestField);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $auth0_logout_url."?".$requestParameter,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_FOLLOWLOCATION =>true,
                CURLOPT_HTTPHEADER => array(
                    "content-type: application/json"
                ),
            ));
            $redirectURL = curl_getinfo($curl,CURLINFO_EFFECTIVE_URL );
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
        }
    }
    
    public function changePassword()
    {
        
    }
    
    public function getAuth0_credentials()
    {
        $storeScope             = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $auth0_enable           = $this->scopeConfig->getValue(self::AUTH0_ENABLE, $storeScope);
        $auth0_domain           = $this->scopeConfig->getValue(self::AUTH0_DOMAIN, $storeScope);
        $auth0_client_id        = $this->scopeConfig->getValue(self::AUTH0_CLIENTID, $storeScope);
        $auth0_client_secret    = $this->scopeConfig->getValue(self::AUTH0_CLIENT_SECRET, $storeScope);
        $auth0_db               = $this->scopeConfig->getValue(self::AUTH0_DB, $storeScope);
        
         
        if(!$auth0_enable || $auth0_domain =='' || $auth0_client_id =='' || $auth0_client_secret=='' )
        {
            return false;
        }
        elseif($auth0_domain!='' && $auth0_client_id !='' && $auth0_client_secret!='')
        {
            $authCred['enable']   = $auth0_enable;  
            $authCred['domain']   = $auth0_domain;  
            $authCred['clientId']   = $auth0_client_id;  
            $authCred['clinet_secret']   = $auth0_client_secret;  
            $authCred['db']   = $auth0_db;
            return $authCred;
        }
        elseif( $auth0_domain=='' && $auth0_client_id=='' && $auth0_client_secret=='')
        {
            $customRedirectionUrl   =   $this->url->getUrl('customer/account/create/');
            $this->messageManager->addError( __('Some configuration field is missing, please contact adminstrator for more information.') );
            $this->_responseFactory->create()->setRedirect($customRedirectionUrl)->sendResponse();
            exit();
        }
        
        
    }
    
    public function auth0Enabled()
    {
        $storeScope             = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $auth0_enable           = $this->scopeConfig->getValue(self::AUTH0_ENABLE, $storeScope);
        $auth0_domain           = $this->scopeConfig->getValue(self::AUTH0_DOMAIN, $storeScope);
        $auth0_client_id        = $this->scopeConfig->getValue(self::AUTH0_CLIENTID, $storeScope);
        $auth0_client_secret    = $this->scopeConfig->getValue(self::AUTH0_CLIENT_SECRET, $storeScope);
        $auth0_db               = $this->scopeConfig->getValue(self::AUTH0_DB, $storeScope);
        
        if(!$auth0_enable || $auth0_domain =='' || $auth0_client_id =='' || $auth0_client_secret=='' || $auth0_db=='' )
        {
            return false;
        }
        else
        {
            return true;
        }
    }

}
