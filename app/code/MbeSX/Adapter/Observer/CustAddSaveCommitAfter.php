<?php
namespace MbeSX\Adapter\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use MbeSX\Adapter\Helper\Data as mbeSXAdapterHelper;
use Magento\Framework\App\State;
use Magento\Framework\Registry;
use Magento\Customer\Model\Session as customerSession;

class CustAddSaveCommitAfter implements ObserverInterface
{
    /**
     * @var array
     */
    protected $customerAddressInfo=[];

    /**
     * @var Magento\Framework\App\State
     */
    private $state;

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

    /**
     * CustAddSaveCommitAfter constructor.
     * @param mbeSXAdapterHelper $mbeSXAdapterHelper
     * @param State $state
     * @param Registry $registry
     * @param customerSession $customerSession
     */
    public function __construct(
        mbeSXAdapterHelper $mbeSXAdapterHelper,
        State $state,
        Registry $registry,
        customerSession $customerSession
    ){
        $this->mbeSXAdapterHelper = $mbeSXAdapterHelper;
        $this->state = $state;
        $this->registry = $registry;
        $this->customerSession = $customerSession;
    }

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        try {
            $customerAddressAfter = $observer->getEvent()->getCustomerAddress();
            $custAddId = $customerAddressAfter->getId();

            $addressInfo = [];
            $addressInfo['parent_id'] = $customerAddressAfter->getParentId();
            $addressInfo['customer_id'] = $customerAddressAfter->getCustomerId();
            $addressInfo['region'] = $customerAddressAfter->getRegion();
            $addressInfo['region_id'] = $customerAddressAfter->getRegionId();
            $addressInfo['country_id'] = $customerAddressAfter->getCountryId();
            $addressInfo['street'] = $customerAddressAfter->getStreet();
            $addressInfo['company'] = $customerAddressAfter->getCompany();
            $addressInfo['telephone'] = $customerAddressAfter->getTelephone();
            $addressInfo['fax'] = $customerAddressAfter->getFax();
            $addressInfo['postcode'] = $customerAddressAfter->getPostcode();
            $addressInfo['city'] = $customerAddressAfter->getCity();
            $addressInfo['firstname'] = $customerAddressAfter->getFirstname();
            $addressInfo['lastname'] = $customerAddressAfter->getLastname();
            $addressInfo['middlename'] = $customerAddressAfter->getMiddlename();
            $addressInfo['default_shipping'] = $customerAddressAfter->getDefaultShipping();
            $addressInfo['default_billing'] = $customerAddressAfter->getDefaultBilling();
            $addressInfo['is_default_billing'] = $customerAddressAfter->getIsDefaultBilling();
            $addressInfo['is_default_shipping'] = $customerAddressAfter->getIsDefaultShipping();
            $addressInfo['store_id'] = $customerAddressAfter->getStoreId();
            $addressInfo['created_at'] = $customerAddressAfter->getCreatedAt();
            $addressInfo['updated_at'] = $customerAddressAfter->getUpdatedAt();
            $addressInfo['entity_id'] = $customerAddressAfter->getEntityId();
            $addressInfo['id'] = $customerAddressAfter->getId();


            if ($this->state->getAreaCode() == 'frontend') {
                $addressInfo = $this->mbeSXAdapterHelper->setSalesAddressInfo($addressInfo);
            } else {
                $addressInfo['is_default_billing'] = !empty($addressInfo['is_default_billing']) ? '1' : '0';
                $addressInfo['is_default_shipping'] = !empty($addressInfo['is_default_shipping']) ? '1' : '0';
            }
            $this->registry->unregister($addressInfo['parent_id']);

            if(!empty($this->customerSession->getCustomerAddressInfo())){
                $this->customerAddressInfo = $this->customerSession->getCustomerAddressInfo();
            }

            $this->customerAddressInfo['after'][$custAddId] = $addressInfo;

            $this->customerSession->setCustomerAddressInfo($this->customerAddressInfo);

        } catch (Exception $e) {
            Mage::log("customer_address_save_after observer failed: " . $e->getMessage());
        }
    }
}