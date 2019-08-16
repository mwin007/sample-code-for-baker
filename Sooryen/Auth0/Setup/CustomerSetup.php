<?php

namespace Sooryen\Auth0\Setup;

use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Setup\Context;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory;

class CustomerSetup extends EavSetup 
{
    protected $eavConfig;
    
    public function __construct(
        ModuleDataSetupInterface $setup,
        Context $context,
        CacheInterface $cache,
        CollectionFactory $attrGroupCollectionFactory,
        Config $eavConfig
        ) 
    {
        $this -> eavConfig = $eavConfig;
        parent :: __construct($setup, $context, $cache, $attrGroupCollectionFactory);
    } 

    public function installAttributes($customerSetup) {
        $this -> installCustomerAttributes($customerSetup);
        $this -> installCustomerAddressAttributes($customerSetup);
    } 

    public function installCustomerAttributes($customerSetup) 
    {
        $attribute_information = array( 'label' => 'auth0_userid', 'system' => 0,
                                        'position' => 100,'sort_order' =>100,
                                        'visible' =>  false,'note' => '',
                                        'type' => 'varchar','input' => 'text'
                                        );
	$customerSetup->addAttribute(\Magento\Customer\Model\Customer::ENTITY,'auth0_userid',$attribute_information );
        
        $attribute_information = array( 'label' => 'Code', 'system' => 0,
                                        'position' => 100,'sort_order' =>100,
                                        'visible' =>  false,'note' => '',
                                        'type' => 'varchar','input' => 'text'
                                        );
	$customerSetup->addAttribute(\Magento\Customer\Model\Customer::ENTITY,'auth0_code',$attribute_information );
        

        $customerSetup->getEavConfig()->getAttribute('customer', 'auth0_code')
            ->setData('is_user_defined',0)
            ->setData('is_required',0)
            ->setData('default_value','')
            ->setData('used_in_forms', [''])
            ->save();
        $attribute_information = array( 'label' => 'Token','system' => 0,
                                        'position' => 100,'sort_order' =>100,
                                        'visible' =>  false,'note' => '',
                                        'type' => 'varchar','input' => 'text'
                                        );
	$customerSetup->addAttribute(\Magento\Customer\Model\Customer::ENTITY,'auth0_token',$attribute_information );

	$customerSetup->getEavConfig()->getAttribute('customer','auth0_token')
                ->setData('is_user_defined',0)
                ->setData('is_required',0)
                ->setData('default_value','')
                ->setData('used_in_forms', [''])
                ->save();

				
	} 

	public function installCustomerAddressAttributes($customerSetup) {
			
	} 

	public function getEavConfig() {
		return $this -> eavConfig;
	} 
} 