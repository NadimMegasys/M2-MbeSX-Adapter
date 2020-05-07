<?php
namespace MbeSX\Adapter\Helper;

use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Customer\Model\Group as customerGroupCollection;
use Magento\Sales\Api\Data\OrderInterface as orderData;
use Magento\Directory\Model\Region as region;
use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface;

class Order extends \MbeSX\Adapter\Helper\CoreHelper
{

    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var \Magento\Customer\Model\Group
     */
    protected $customerGroupCollection;

    /**
     * @var \Magento\Sales\Api\Data\OrderInterface
     */
    protected $order;

    /**
     * @var \Magento\Directory\Model\Region
     */
    protected $region;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;


    /**
     * Order constructor.
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param customerGroupCollection $customerGroupCollection
     * @param orderData $order
     * @param region $region
     * @param ProductRepository $productRepository
     * @param CustomerFactory $customerFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        OrderItemRepositoryInterface $orderItemRepository,
        customerGroupCollection $customerGroupCollection,
        orderData $order,
        region $region,
        ProductRepository $productRepository,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager
    ){
        $this->orderItemRepository = $orderItemRepository;
        $this->customerGroupCollection = $customerGroupCollection;
        $this->order = $order;
        $this->region = $region;
        $this->productRepository = $productRepository;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getCustomerById($id) {
        return $this->customerFactory->create()->load($id);
    }

    /**
     * Get Customer Address details
     *
     * @param array $allowedFields
     * @param array $whiteListArr
     * @param array $tableData
     * @param array $moduleConfig
     * @return type array
     */
    public function getOrderHeaderInfo($allowedFields, $whiteListArr, $tableData,$moduleConfig)
    {
        $orderInfo = [];
        foreach ($allowedFields[$tableData['tableName']]['mssql'] as $key => $field) {
            if (isset($tableData['after'][$key]) && ! isset($field['association'])) {
                $orderInfo[$field['map']] = $tableData['after'][$key];
            } else {
                $orderInfo[$field['map']] = $this->getOrderHeaderInfoValue($tableData['after'], $key,$moduleConfig);
            }

            if (isset($field['dataType'])) {
                $orderInfo[$field['map']] = $this->setDataType($orderInfo[$field['map']], $field['dataType']);
            }
        }

        return $orderInfo;
    }

    /**
     * Get Customer Address details
     *
     * @param array $allowedFields
     * @param array $tableData
     * @param array $moduleConfig
     * @return array $orderInfo
     */
    public function getOrderLineInfo($allowedFields, $tableData,$moduleConfig)
    {
        $orderInfo = [];
        foreach ($allowedFields[$tableData['tableName']]['mssql'] as $key => $field) {
            if (isset($tableData['after'][$key]) && ! isset($field['association'])) {
                $orderInfo[$field['map']] = $tableData['after'][$key];
            } else {
                $orderInfo[$field['map']] = $this->getOrderLineInfoValue($tableData['after'], $key,$moduleConfig);
            }
            if (isset($field['dataType'])) {
                $orderInfo[$field['map']] = $this->setDataType($orderInfo[$field['map']], $field['dataType']);
            }
        }
        return $orderInfo;
    }

    /**
     * Get Customer Value for each Address fields
     *
     * @param array $orderData
     * @param string $field
     * @param array $moduleConfig
     * @return int
     */
    private function getOrderHeaderInfoValue($orderData, $field,$moduleConfig)
    {
        $value = false;
        $order = $this->order->load($orderData['entity_id']);
        $customer = $this->getCustomerById($orderData['customer_id']);

        $billingAddress = $this->order->getBillingAddress();
        $shippingAddress = $this->order->getShippingAddress();

        switch ($field) {
            case "store_id":
                $value = $order->getStoreId();
                break;
            case "site_id":
                $value = $moduleConfig['site_id'];
                break;
            case "website_id":
                $value = $this->storeManager->getStore($order->getStoreId())->getWebsiteId();
                break;
            case "order_number":
                $value = $order->getIncrementId();
                break;
            case "status":
                $value = $order->getStatusLabel();
                break;
            case "pay_method":
                $value = $order->getPayment()->getMethod();
                break;
            case "pay_info":
                $info = $this->_getPayInfo($order);
                $value = $info['pay_info'];
                break;
            case "pay_success":
                $info = $this->_getPayInfo($order);
                $value = $info['pay_success'];
                break;
            case "discount_type":
                $value = "$";
                break;
            case "group_name":
                $value = $this->customerGroupCollection->load($customer->getGroupId())
                    ->getCustomerGroupCode();
                break;
            case "shipping_address_id":
                $value = $shippingAddress->getCustomerAddressId();
                break;
            case "shipping_firstname":
                $value = $shippingAddress->getFirstname();
                break;
            case "shipping_lastname":
                $value = $shippingAddress->getLastname();
                break;
            case "shipping_company":
                $value = $shippingAddress->getCompany();
                break;
            case "shipping_country":
                $value = $shippingAddress->getCountryId();
                break;
            case "shipping_postcode":
                $value = $shippingAddress->getPostcode();
                break;
            case "shipping_city":
                $value = $shippingAddress->getCity();
                break;
            case "shipping_state":
                $value = $shippingAddress->getRegion();
                break;
            case "shipping_street1":
                $value = $shippingAddress->getStreet()[0];
                break;
            case "shipping_phone":
                $value = $shippingAddress->getTelephone();
                break;
            case "shipping_email":
                $value = $shippingAddress->getEmail();
                break;
            case "billing_address_id":
                $value = $billingAddress->getCustomerAddressId();
                break;
            case "billing_firstname":
                $value = $billingAddress->getFirstname();
                break;
            case "billing_lastname":
                $value = $billingAddress->getLastname();
                break;
            case "billing_company":
                $value = $billingAddress->getCompany();
                break;
            case "billing_country":
                $value = $billingAddress->getCountryId();
                break;
            case "billing_postcode":
                $value = $billingAddress->getPostcode();
                break;
            case "billing_city":
                $value = $billingAddress->getCity();
                break;
            case "billing_state":
                $value = $billingAddress->getRegion();
                break;
            case "billing_street1":
                $value = $billingAddress->getStreet()[0];
                break;
            case "billing_phone":
                $value = $billingAddress->getTelephone();
                break;
            case "billing_email":
                $value = $billingAddress->getEmail();
                break;
            case "shipping_method_label":
                $value = $order->getShippingDescription();
                break;
            case "shipping_method":
                $value = $order->getShippingMethod();
                break;
            case "disposition":
                $value = $order->getDisposition();
                break;
            case "customer_note":
                $value = $order->getCustomerNote();
                break;
            case "personal_shipping":
                $value = $order->getPersonalShipping();
                break;
            default:
                $value;
        }

        return $value;
    }

    /**
     * @param $orderData
     * @param $field
     * @param $moduleConfig
     * @return bool
     */
    private function getOrderLineInfoValue($orderData, $field,$moduleConfig)
    {
        $value = false;
        switch ($field) {
            case "store_id":
                $value = $orderData['store_id'];
                break;
            case "site_id":
                $value = $moduleConfig['site_id'];
                break;
            case "OrderLn":
                $value = $orderData['item_id'];
                break;
            case "website_id":
                $value = $this->storeManager->getStore($orderData['store_id'])->getWebsiteId();
                break;
            case "original_price":
                $value = $orderData['original_price'];
                break;
            case "erp_part_number":
                $prodObj = $this->productRepository->get($orderData['sku']);
                $value = $prodObj->getData('erp_part_number');
                break;
            default:
                $value;
        }

        return $value;
    }

    private function getCustomerAddressInfo($id, $field)
    {
        $address = $this->order->load($id);
        switch ($field) {
            case "address_id":
                $value = $address->getCustomerAddressId();
                break;
            case "firstname":
                $value = $address->getFirstname();
                break;
            case "lastname":
                $value = $address->getLastname();
                break;
            case "city":
                $value = $address->getCity();
                break;
            case "company":
                $value = $address->getCompany();
                break;
            case "country":
                $value = $address->getCountryId();
                break;
            case "postcode":
                $value = $address->getPostcode();
                break;
            case "state":
                if ($address->getRegionId()) {
                    $region = $this->region->load($address->getRegionId());
                    $value = $region ? $region->getCode() : $address->getRegion();
                } else {
                    $value = $address->getRegion();
                }
                break;
            case "street1":
                $value = $address->getStreet(1);
                break;
            case "street2":
                $value = $address->getStreet(2);
                break;
            case "phone":
                $value = $address->getTelephone();
                break;
            default:
                $value;
        }

        return $value;
    }

    /**
     * Paymen informations of an order
     *
     * @param object $order
     * @return array
     */
    private function _getPayInfo($order)
    {
        $method = $order->getPayment()->getMethod();
        $info = '';
        switch ($method) {
            case "purchaseorder":
                $info = $order->getPayment()->getPoNumber();
                break;
            case "paypal_express":
                $info = $order->getPayment()->getLastTransId();
                break;
            case "paypal_direct":
                $info = $order->getPayment()->getLastTransId();
                break;
            case "checkmo":
                $info = $order->getPayment()
                    ->getMethodInstance()
                    ->getPayableTo();
                // $value = "PayableTo: ".$info.", Check to: ".$order->getPayment()->getMethodInstance()->getMailingAddress();
                break;
            case "eaton_bpo":
                $info = $order->getPayment()->getBpoCode();
                break;
        }

        $status = ! empty($info) ? true : false;

        return array(
            "pay_info" => $info,
            "pay_success" => $status
        );
    }

    private function _getShipmentInfo($order, $field)
    {
        $value = null;
        $shippingInfo = $order->getShippingMethod(true);
        switch ($field) {
            case "shipping_method_label":
                $value = $order->getShippingDescription();
                break;
            case "shipping_method":
                $value = $shippingInfo->getData('method');
                break;
            case "disposition":
                $value = $order->getData('disposition');
                break;
            case "customer_note":
                $value = $order->getData('customer_note');
                break;
        }

        return $value;
    }
}
