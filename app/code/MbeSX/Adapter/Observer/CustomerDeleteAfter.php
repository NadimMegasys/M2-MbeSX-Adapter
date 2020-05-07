<?php
namespace MbeSX\Adapter\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use MbeSX\Adapter\Helper\Data as MbeSXAdapterHelper;
use Magento\Customer\Model\Session as customerSession;

class CustomerDeleteAfter implements ObserverInterface
{
    /**
     * @var array
     */
    protected $customerInfo=[];

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession ;

    public function __construct(
        customerSession $customerSession
    ){
        $this->customerSession = $customerSession;
    }

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        if(!empty($this->customerSession->getCustomerInfo())){
            $this->customerInfo = $this->customerSession->getCustomerInfo();
        }
        $customerDeleteAfter = $observer->getEvent()->getCustomer();
        $this->customerInfo['before'][] = MbeSXAdapterHelper::DELETED_ENTITY_METHOD;
        $this->customerInfo['after'][] = $customerDeleteAfter->getData();
        $this->customerSession->setCustomerInfo($this->customerInfo);
    }
}