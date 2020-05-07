<?php
namespace MbeSX\Adapter\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use MbeSX\Adapter\Helper\Data as MbeSXAdapterHelper;
use Magento\Framework\Registry;
use Magento\Customer\Model\Session as customerSession;

class CustomerSaveCommitAfter implements ObserverInterface
{
    /**
     * @var array
     */
    public $customerInfo=[];

    /**
     * @var MbeSX\Adapter\Helper\Data
     */
    private $mbeSXAdapterHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession ;

    public function __construct(
        mbeSXAdapterHelper $mbeSXAdapterHelper,
        Registry $registry,
        customerSession $customerSession
    ){
        $this->mbeSXAdapterHelper = $mbeSXAdapterHelper;
        $this->registry = $registry;
        $this->customerSession = $customerSession;
    }

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        try {
            $customerAfter = $observer->getEvent()->getCustomer();
            $data = ! empty($customerAfter->getOrigData()) ? $customerAfter->getOrigData() : '1';
            $customerAfterData = $customerAfter->getData();

            if(!empty($this->customerSession->getCustomerInfo())){
                $this->customerInfo = $this->customerSession->getCustomerInfo();
            }

            $this->registry->unregister($customerAfterData['entity_id']);
            $this->customerInfo['before'][] = $data;
            $this->customerInfo['after'][] = $customerAfterData;

            $this->customerSession->setCustomerInfo($this->customerInfo);

        } catch (Exception $e) {
            Mage::log("customer_save_after observer failed: " . $e->getMessage());
        }
    }
}