<?php
namespace MbeSX\Adapter\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use MbeSX\Adapter\Helper\Data as MbeSXAdapterHelper;
use Magento\Customer\Model\Session as customerSession;

class CustomerAddressDeleteAfter implements ObserverInterface
{
    /**
     * @var array
     */
    protected $customerAddressInfo=[];

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

        if(!empty($this->customerSession->getCustomerAddressInfo())){
            $this->customerAddressInfo = $this->customerSession->getCustomerAddressInfo();
        }
        $customerAddressDeleteAfter = $observer->getEvent()->getCustomerAddress();

        $deletedAddressInfo['id'] = $customerAddressDeleteAfter->getId();
        $deletedAddressInfo['entity_id'] = $customerAddressDeleteAfter->getEntityId();
        $deletedAddressInfo['entity_type_id'] = $customerAddressDeleteAfter->getEntityTypeId();
        $deletedAddressInfo['attribute_set_id'] = $customerAddressDeleteAfter->getAttributeSetId();
        $deletedAddressInfo['increment_id'] = $customerAddressDeleteAfter->getIncrementId();
        $deletedAddressInfo['parent_id'] = $customerAddressDeleteAfter->getParentId();
        $deletedAddressInfo['created_at'] = $customerAddressDeleteAfter->getCreatedAt();
        $deletedAddressInfo['updated_at'] = $customerAddressDeleteAfter->getUpdatedAt();
        $deletedAddressInfo['is_active'] = $customerAddressDeleteAfter->getIsActive();
        $deletedAddressInfo['prefix'] = $customerAddressDeleteAfter->getPrefix();
        $deletedAddressInfo['firstname'] = $customerAddressDeleteAfter->getFirstname();
        $deletedAddressInfo['lastname'] = $customerAddressDeleteAfter->getLastname();
        $deletedAddressInfo['suffix'] = $customerAddressDeleteAfter->getSuffix();
        $deletedAddressInfo['company'] = $customerAddressDeleteAfter->getCompany();
        $deletedAddressInfo['country_id'] = $customerAddressDeleteAfter->getCountryId();
        $deletedAddressInfo['postcode'] = $customerAddressDeleteAfter->getPostcode();
        $deletedAddressInfo['city'] = $customerAddressDeleteAfter->getCity();
        $deletedAddressInfo['telephone'] = $customerAddressDeleteAfter->getTelephone();
        $deletedAddressInfo['fax'] = $customerAddressDeleteAfter->getFax();
        $deletedAddressInfo['vat_id'] = $customerAddressDeleteAfter->getVatId();
        $deletedAddressInfo['region_id'] = $customerAddressDeleteAfter->getRegionId();
        $deletedAddressInfo['region'] = $customerAddressDeleteAfter->getRegion();
        $deletedAddressInfo['street'] = $customerAddressDeleteAfter->getStreet()[0];
        $deletedAddressInfo['is_default_billing'] = $customerAddressDeleteAfter->getIsDefaultBilling();
        $deletedAddressInfo['is_default_shipping'] = $customerAddressDeleteAfter->getIsDefaultShipping();
        $deletedAddressInfo['customer_id'] = $customerAddressDeleteAfter->getCustomerId();

        $custAddId = $customerAddressDeleteAfter->getId();
        $this->customerAddressInfo['before'][$custAddId] = MbeSXAdapterHelper::DELETED_ENTITY_METHOD;
        $this->customerAddressInfo['after'][$custAddId] = $deletedAddressInfo;

        $this->customerSession->setCustomerAddressInfo($this->customerAddressInfo);
    }
}