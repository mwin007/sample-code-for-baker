<?php 
namespace Sooryen\Auth0\Controller\Login;
use Magento\Framework\UrlInterface;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\CustomerFactory;
class Authorize extends \Magento\Framework\App\Action\Action
{
    public function __construct(\Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        UrlInterface $url,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepo,
        \Magento\Customer\Model\Session $customerSession,
        \Sooryen\Auth0\Helper\Data $helper,
        StoreManagerInterface $storeManager,
        CustomerFactory $customerFactory
    )
    {
        $this->storeManager     = $storeManager;
        $this->messageManager = $context->getMessageManager();
	$this->_pageFactory = $pageFactory;
        $this->scopeConfig = $scopeConfig;
        $this->_responseFactory = $responseFactory;
        $this->url = $url;
        $this->customerRepo = $customerRepo;
        $this->_customerSession = $customerSession;
        $this->helper           = $helper;
        $this->customerFactory  = $customerFactory;
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
            $request= $this->getRequest()->getParams();
            $authorize_uri = $this->url->getUrl('auth0/login/authorize');
            if(isset($request['code']) && $request['code']!='')
            {
                $code           = $request['code'];
                $token_url      = $authCred['domain'].'oauth/token';
                $postField = array( 'client_id'=>$authCred['clientId'],
                                    'client_secret'=>$authCred['clinet_secret'],
                                    'redirect_uri'=>$authorize_uri,
                                    'grant_type'=>'authorization_code',
                                    'code'=> $code
                            );
                $postField = json_encode($postField);
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $token_url,
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
                $response   =   curl_exec($curl);
                $res        =   json_decode($response);
                $err        =   curl_error($curl);
                curl_close($curl);
                if ($err) 
                {
                    echo "cURL Error #:" . $err;
                } 
                elseif(isset($res->error) && $res->error!='')
                {
                    $customRedirectionUrl = $this->url->getUrl();
                    $this->messageManager->addError( __($res->error_description) );
                    $this->_responseFactory->create()->setRedirect($customRedirectionUrl)->sendResponse();
                    exit();
                }
                else 
                {
                    $token      =   $res->access_token;
                    if($token)
                    {
                        $auth0_profile_url    = $authCred['domain'].'userinfo';
                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => $auth0_profile_url,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_CUSTOMREQUEST => "GET",
                            CURLOPT_HTTPHEADER => array(
                                "content-type: application/x-www-form-urlencoded",
                                "Authorization: Bearer ". $token 
                            ),
                        ));
                        $response = curl_exec($curl);
                        $err = curl_error($curl);
                        curl_close($curl);
                        $js_res        =   json_decode($response);
                    
                        if ($err) {
                            echo "cURL Error #:" . $err;
                        } 
                        else 
                        {
                            $userToken = $token;
                            if(isset($js_res->email) && $js_res->email!='' /*&& $js_res->email_verified*/)
                            {
                                $userEmail  =   $js_res->email;
                                $firstName  =   $js_res->nickname;
                                $lastName   =   $js_res->name;
                                $customer_fact = $this->customerFactory->create();
                                $websiteId  = $this->storeManager->getWebsite()->getWebsiteId();
                                $customer_fact->setWebsiteId($websiteId);
                                
                                if($customer_fact->loadByEmail($userEmail)->getId()=='')
                                {
                                    
                                    // We need to create customer programmtically 
                                    $customer_fact->setEmail($userEmail); 
                                    $customer_fact->setFirstname($firstName);
                                    $customer_fact->setLastname($lastName);
                                    $customer_fact->setCustomAttribute('auth0_token',$userToken);
                                    $customer_fact->setPassword(null); // set null to auto-generate password
                                    // set the customer as confirmed
                                    // this is optional
                                    // comment out this line if you want to send confirmation email
                                    // to customer before finalizing his/her account creation
                                    $customer_fact->setForceConfirmed(true);
                                    // save data
                                    $customer_fact->save();
                                }
                                $customer=$this->customerRepo->get($userEmail);
                                $customer->setCustomAttribute('auth0_token',$userToken);
                                $this->customerRepo->save($customer);
                                $this->_customerSession->setCustomerDataAsLoggedIn($customer);
                                $this->_customerSession->regenerateId();
                                $this->messageManager->addSuccess( __('You have successfully logged in store.') );
                                $customRedirectionUrl = $this->url->getUrl('customer/account');
                                $this->_responseFactory->create()->setRedirect($customRedirectionUrl)->sendResponse();
                                exit();
                            }
                        }
                    }
                }
            }
            
            
        }
        catch (\Exception $e) 
        {
            $customRedirectionUrl   =   $this->url->getUrl('customer/account/');
            $this->messageManager->addError( __('Invalid signup '.$e->getMessage()) );
            $this->_responseFactory->create()->setRedirect($customRedirectionUrl)->sendResponse();
            exit();
        }
        
        
        
        
    }
}