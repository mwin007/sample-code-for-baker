<?php

namespace Sooryen\Auth0\Model\ResourceModel;

/**
 * Auth0 resource
 */
class Auth0 extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('sooryen_auth0', 'id');
    }

  
}
