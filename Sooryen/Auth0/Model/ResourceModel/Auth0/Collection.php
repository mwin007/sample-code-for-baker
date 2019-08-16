<?php

namespace Sooryen\Auth0\Model\ResourceModel\Auth0;

/**
 * Auth0s Collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Initialize resource collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Sooryen\Auth0\Model\Auth0', 'Sooryen\Auth0\Model\ResourceModel\Auth0');
    }
}
