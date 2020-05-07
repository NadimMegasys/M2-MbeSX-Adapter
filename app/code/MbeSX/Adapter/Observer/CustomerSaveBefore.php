<?php
namespace MbeSX\Adapter\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use MbeSX\Adapter\Helper\Data as MbeSXAdapterHelper;
use Magento\Framework\Registry;

class CustomerSaveBefore implements ObserverInterface
{
    /**
     * @var array
     */
    protected $customerInfo=[];

    /**
     * @var MbeSX\Adapter\Helper\Data
     */
    private $mbeSXAdapterHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * CustomerSaveBefore constructor.
     * @param MbeSXAdapterHelper $mbeSXAdapterHelper
     * @param Registry $registry
     */
    public function __construct(
        mbeSXAdapterHelper $mbeSXAdapterHelper,
        Registry $registry
    ){
        $this->mbeSXAdapterHelper = $mbeSXAdapterHelper;
        $this->registry = $registry;
    }

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        try {
            $customerBefore = $observer->getEvent()->getCustomer();
            $customerBeforeData = $customerBefore->getOrigData();
            $customer = $this->mbeSXAdapterHelper->getCustomerById($customerBeforeData['entity_id']);

            if (isset($customerBeforeData['entity_id'])) {
                $this->registry->register($customerBeforeData['entity_id'], array(
                    'is_default_shipping' => $customer->getDefaultShipping(),
                    'is_default_billing' => $customer->getDefaultBilling()
                ));

                $this->registry->registry($customerBeforeData['entity_id']);
        }
        } catch (Exception $e) {
            Mage::log("customer_save_after observer failed: " . $e->getMessage());
        }
    }
}