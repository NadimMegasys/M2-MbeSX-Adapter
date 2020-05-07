<?php
namespace MbeSX\Adapter\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use Magento\Customer\Api\GroupRepositoryInterface as GroupRepositoryInterface;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;

class Customer extends \MbeSX\Adapter\Helper\CoreHelper
{
    protected $customer = null;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * @var \Magento\Store\Model\ResourceModel\Website\CollectionFactory
     */
    protected $websiteCollectionFactory;

    /**
     * Get Customer Address details
     *
     * @param array $allowedFields
     * @param array $whiteListArr
     * @param array $tableData
     * @return array
     */

    /**
     * Customer constructor.
     * @param CustomerFactory $customerFactory
     * @param StoreManagerInterface $storeManager
     * @param GroupRepositoryInterface $groupRepository
     * @param WebsiteCollectionFactory $websiteCollectionFactory
     */
    public function __construct(
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        GroupRepositoryInterface $groupRepository,
        WebsiteCollectionFactory $websiteCollectionFactory
    ){
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->groupRepository = $groupRepository;
        $this->websiteCollectionFactory = $websiteCollectionFactory;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getCustomerById($id) {
        return $this->customerFactory->create()->load($id);
    }

    /**
     * @param $allowedFields
     * @param $whiteListArr
     * @param $tableData
     * @param $moduleConfig
     * @return array
     */
    public function getCustomerAddressInfo($allowedFields, $whiteListArr, $tableData,$moduleConfig)
    {
        $customerAddressInfo = [];
        foreach ($allowedFields[$tableData['tableName']]['mssql'] as $key => $field) {
            if (isset($tableData['after'][$key]) && ! isset($field['association'])) {
                $customerAddressInfo[$field['map']] = $tableData['after'][$key];
            } else {
                $customerAddressInfo[$field['map']] = $this->getCustomerAddressValue($tableData['after'], $key,$moduleConfig);
            }

            if (isset($field['dataType'])) {
                $customerAddressInfo[$field['map']] = $this->setDataType($customerAddressInfo[$field['map']], $field['dataType']);
            }
        }
        return $customerAddressInfo;
    }

    /**
     * @param $custData
     * @param $field
     * @param $moduleConfig
     * @return bool|mixed|string
     */
    public function getCustomerAddressValue($custData, $field,$moduleConfig)
    {
        $value = false;
        $customer = $this->getCustomer($custData['parent_id']);

        switch ($field) {
            case "store_id":
                $value = $this->storeManager->getStore()->getId();
                break;
            case "site_id":
                $value = $moduleConfig['site_id'];
                break;
            case "website_id":
                $value = $customer->getWebsiteId();
                break;
            case "group_id":
                $value = $customer->getGroupId();
                break;
            case "group_name":
                $value = $this->groupRepository->getById($customer->getGroupId())->getCode();
                break;
            case "is_default_billing":
                $value = $custData['is_default_billing'] == '1' ? '1' : '0';
                break;
            case "is_default_shipping":
                $value = $custData['is_default_shipping'] == '1' ? '1' : '0';
                break;
            case "street1":
                $street = explode(PHP_EOL, $custData['street'][0]);
                $value = ! empty($street[0]) ? $street[0] : '';
                break;
            case "region":
                if (! empty($custData['region_id'])) {
                    $value = $custData['region_id'];
                } else {
                    $value = $custData['region'];
                }
                break;
            case "email":
                $value = $customer->getData('email');
                break;
            case "customercodeshipping":
                $value = $customer->getCustomercodeshipping();
                break;
            default:
                $value;
        }

        return $value;
    }


    /**
     * @param $allowedFields
     * @param $whiteListArr
     * @param $tableData
     * @param $moduleConfig
     * @return array
     */
    public function getCustomerInfo($allowedFields, $whiteListArr, $tableData,$moduleConfig)
    {
        $customerInfo = [];
        foreach ($allowedFields[$tableData['tableName']]['mssql'] as $key => $field) {
            if (isset($tableData['after'][$key]) && ! isset($field['association'])) {
                $customerInfo[$field['map']] = $tableData['after'][$key];
            } else {
                $customerInfo[$field['map']] = $this->getCustomerInfoValue($tableData['after'], $key,$moduleConfig);
            }

            if (isset($field['dataType'])) {
                $customerInfo[$field['map']] = $this->setDataType($customerInfo[$field['map']], $field['dataType']);
            }
        }

        return $customerInfo;
    }

    /**
     * Get Customer Information Values by field
     *
     * @param array $custData
     * @param string $field
      * @param $moduleConfig
     * @return type string
     */
    public function getCustomerInfoValue($custData, $field,$moduleConfig)
    {
        $value = false;
        $customer = $this->getCustomerById($custData['entity_id']);
        switch ($field) {
            case "store_id":
                $value = $this->storeManager->getStore()->getId();
                break;
            case "site_id":
                $value = $moduleConfig['site_id'];
                break;
            case "group_name":
                $value = $this->groupRepository->getById($custData['group_id'])->getCode();
                break;
            case "website_name":
                $value = $this->getWebsiteName($custData['website_id']);
                break;
            case "is_active":
                $value = $customer->getIsActive();
                break;
            case "created_at":
                $value = date("Y-m-d H:i:s", strtotime($customer->getCreatedAt()));
                break;
            default:
                $value = false;
        }

        return $value;
    }

    /**
     * Get website name by website id
     *
     * @param int $websiteId
     * @return string
     */
    public function getWebsiteName($websiteId)
    {
        $collection = $this->websiteCollectionFactory->create()->load($websiteId,'website_id');
        $websiteData = $collection->getData();

        return $websiteData[0]['name'];
    }

    /**
     * Returns Customer Object for given Customer ID
     *
     * @param int $custID            
     * @return Mage_Core_Model_Abstract
     */
    protected function getCustomer($custID)
    {
        if (! $this->customer) {
            $this->customer = $this->getCustomerById($custID);
        }
        return $this->customer;
    }
}
