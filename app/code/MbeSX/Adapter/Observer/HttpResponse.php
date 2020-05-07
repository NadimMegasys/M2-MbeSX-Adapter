<?php

namespace MbeSX\Adapter\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use MbeSX\Adapter\Helper\Data as mbeSXAdapterHelper;
use Magento\Customer\Model\Session as customerSession;

class HttpResponse implements ObserverInterface
{

    /**
     * @var array
     */
    protected $customerDetails = [];

    /**
     * @var array
     */
    protected $customerAddressInfo = [];

    /**
     * @var array
     */
    protected $orderDetails = [];

    /**
     * @var MbeSX\Adapter\Helper\Data
     */
    private $mbeSXAdapterHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * HttpResponse constructor.
     * @param mbeSXAdapterHelper $mbeSXAdapterHelper
     * @param customerSession $customerSession
     */
    public function __construct(
        mbeSXAdapterHelper $mbeSXAdapterHelper,
        customerSession $customerSession
    )
    {
        $this->mbeSXAdapterHelper = $mbeSXAdapterHelper;
        $this->customerSession = $customerSession;
    }


    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->mbeSXAdapterHelper->isEnabled()) {
            return;
        }

        if (!empty($this->customerSession->getCustomerInfo())) {
            $customerInfo = $this->customerSession->getCustomerInfo();
            $this->customerSession->unsCustomerInfo();
        }

        if (!empty($customerInfo)) {
            foreach ($customerInfo['after'] as $key => $value) {
                $this->customerDetails[] = array(
                    'tableName' => 'InCustomer',
                    'before' => $customerInfo['before'][$key],
                    'after' => $value
                );
            }
        }

        if (!empty($this->customerSession->getCustomerAddressInfo())) {
            $customerAddressInfo = $this->customerSession->getCustomerAddressInfo();
            $this->customerSession->unsCustomerAddressInfo();
        }

        if (!empty($customerAddressInfo)) {
            if (isset($customerAddressInfo['after'])) {
                foreach ($customerAddressInfo['after'] as $key => $value) {
                    $customerAddressInfo['before'][$key] = isset($customerAddressInfo['before'][$key]) ? $customerAddressInfo['before'][$key] : 1; // new address
                    $allowFlag = $this->mbeSXAdapterHelper->blockControllersAndActions($customerAddressInfo['before'][$key]);
                    if ($allowFlag) {
                        $this->customerDetails[] = array(
                            'tableName' => 'InCustomerAddress',
                            'before' => $customerAddressInfo['before'][$key],
                            'after' => $value
                        );
                    }
                }
            }
        }
        if (!empty($this->customerDetails)) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/customerDataSync.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $mylog = '<pre>' . print_r($this->customerDetails, true) . '</pre>';
            $logger->info($mylog);
            $this->mbeSXAdapterHelper->customerDataSync($this->customerDetails, $this->getModuleConfig());
        }

        if (!empty($this->customerSession->getOrderDetails())) {
            $this->orderDetails = $this->customerSession->getOrderDetails();
            $this->customerSession->unsOrderDetails();

            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/orderDataSync.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $mylog = '<pre>' . print_r($this->orderDetails, true) . '</pre>';
            $logger->info($mylog);

            $this->mbeSXAdapterHelper->orderDataSync($this->orderDetails, $this->getModuleConfig());
        }
    }

    public function getModuleConfig()
    {
        $moduleConfig = [];
        $moduleConfig['enable'] = $this->mbeSXAdapterHelper->isEnabled();
        $moduleConfig['site_id'] = $this->mbeSXAdapterHelper->getSiteID();

        return $moduleConfig;
    }
}