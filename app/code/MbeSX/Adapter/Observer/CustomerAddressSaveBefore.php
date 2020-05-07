<?php
namespace MbeSX\Adapter\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\AddressFactory;
use MbeSX\Adapter\Helper\Data as mbeSXAdapterHelper;
use Magento\Customer\Model\Session as customerSession;

class CustomerAddressSaveBefore implements ObserverInterface
{
    /**
     * @var array
     */
    protected $customerAddressInfo=[];


    /**
     * @var MbeSX\Adapter\Helper\Data
     */
    private $mbeSXAdapterHelper;

    /**
     * @var Magento\Customer\Model\AddressFactory
     */
    protected $addressFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession ;

    public function __construct(
        mbeSXAdapterHelper $mbeSXAdapterHelper,
        AddressFactory $addressFactory,
        customerSession $customerSession
    ){
        $this->mbeSXAdapterHelper = $mbeSXAdapterHelper;
        $this->addressFactory = $addressFactory;
        $this->customerSession = $customerSession;
    }

    /**
     * @param $addressId
     * @return mixed
     */
    public function getCustomerAddressById($addressId) {
        return $this->addressFactory->create()->load($addressId);
    }

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        try {
            $customerAddressBefore = $observer->getEvent()->getCustomerAddress();
            $custAddId = $customerAddressBefore->getId();

            if(!empty($this->customerSession->getCustomerAddressInfo())){
                $this->customerAddressInfo = $this->customerSession->getCustomerAddressInfo();
            }
            if (! empty($customerAddressBefore->getOrigData())) {
                $addressInfo = $customerAddressBefore->getOrigData();
                $addressInfo = $this->mbeSXAdapterHelper->setSalesAddressInfo($addressInfo);
                $this->customerAddressInfo['before'][$custAddId] = $addressInfo;
            } else {
                if ($customerAddressBefore->isObjectNew()) {
                    $this->customerAddressInfo['before'][] = mbeSXAdapterHelper::MODIFIED_ENTITY_METHOD;
                } else {
                    if (! $customerAddressBefore->isObjectNew() && ! $customerAddressBefore->getOrigData()) {
                        $customerAddressBefore = $this->getCustomerAddressById($customerAddressBefore->getId());

                        foreach ($customerAddressBefore->getData() as $field => $value) {
                            $customerAddressBefore->setOrigData($field, $value);
                        }
                    }
                    $addressInfo = $customerAddressBefore->getOrigData();
                    $addressInfo = $this->mbeSXAdapterHelper->setSalesAddressInfo($addressInfo);
                    $this->customerAddressInfo['before'][$custAddId] = $addressInfo;
                }
            }

            $this->customerSession->setCustomerAddressInfo($this->customerAddressInfo);

        } catch (Exception $e) {
            Mage::log("customer_address_save_after observer failed: " . $e->getMessage());
        }
    }
}