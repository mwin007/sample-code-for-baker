<?php 
namespace Sooryen\Auth0\Plugin;

class EmailNotification
{
    public function aroundNewAccount(\Magento\Customer\Model\EmailNotification $subject, \Closure $proceed)
    {
        /* Disable welcome email notification*/
        return $subject;
    }
}
