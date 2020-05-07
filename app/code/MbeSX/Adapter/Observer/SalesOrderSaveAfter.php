<?php
namespace MbeSX\Adapter\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use MbeSX\Adapter\Helper\Data as MbeSXAdapterHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Customer\Model\Session as customerSession;

class SalesOrderSaveAfter implements ObserverInterface
{
    /**
     * @var array
     */
    protected $orderDetails=[];

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession ;

    /**
     * SalesOrderSaveAfter constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param customerSession $customerSession
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        OrderItemRepositoryInterface $orderItemRepository,
        customerSession $customerSession
    ){
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->orderItemRepository = $orderItemRepository;
        $this->customerSession = $customerSession;
    }

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        $event = $observer->getEvent();
        $order = $event->getOrder();
        $newOrderStatuses = array_filter(explode(',', $this->getNewOrderStatus()));
        $cancelledOrderStatuses = array_filter(explode(',', $this->getCancelledOrderStatuses()));

        $oldStatus = $order->getOrigData('status');
        $newStatus = $order->getStatus();


        $orderData =[];
        $orderData['store_id'] = $order->getStoreId();
        $orderData['entity_id'] = $order->getId();
        $orderData['customer_id'] = $order->getCustomerId();
        $orderData['customer_email'] = $order->getCustomerEmail();
        $orderData['customer_firstname'] = $order->getCustomerFirstname();
        $orderData['customer_lastname'] = $order->getCustomerLastname();
        $orderData['customer_group_id'] = $order->getCustomerGroupId();
        $orderData['base_subtotal'] = $order->getBaseSubtotal();
        $orderData['base_currency_code'] = $order->getBaseCurrencyCode();
        $orderData['base_grand_total'] = $order->getBaseGrandTotal();
        $orderData['shipping_amount'] = $order->getShippingAmount();
        $orderData['shipping_description'] = $order->getShippingDescription();
        $orderData['shipping_method'] = $order->getShippingMethod();
        $orderData['state'] = $order->getState();
        $orderData['status'] = $order->getStatus();
        $orderData['created_at'] = $order->getCreatedAt();
        $orderData['updated_at'] = $order->getUpdatedAt();
        $orderData['shipping_address_id'] = $order->getShippingAddress()->getShippingAddressId();
        $orderData['billing_address_id'] = $order->getBillingAddress()->getBillingAddressId();

        if ($oldStatus != $newStatus) {
            if ($oldStatus === null && in_array($newStatus, $newOrderStatuses)) {
                $this->orderDetails[] = array(
                    'tableName' => 'InOrderHeader',
                    'before' => MbeSXAdapterHelper::NEW_ENTITY_METHOD,
                    'after' => $orderData
                );
                foreach ($order->getAllItems() as $item) {
                    $itemData = [];
                    $itemData['sku']= $item->getSku();
                    $itemData['name']= $item->getName();
                    $itemData['weight']= $item->getWeight();
                    $itemData['qty_ordered']= $item->getQtyOrdered();
                    $itemData['original_price']= $item->getOriginalPrice();
                    $itemData['price']= $item->getPrice();
                    $itemData['base_price']= $item->getBasePrice();
                    $itemData['product_id']= $item->getProductId();
                    $itemData['store_id']= $item->getStoreID();


                    $this->orderDetails[] = array(
                        'tableName' => 'InOrderLine',
                        'before' => MbeSXAdapterHelper::NEW_ENTITY_METHOD,
                        'after' => $itemData
                    );
                }
            } elseif (in_array($newStatus, $cancelledOrderStatuses) && $oldStatus !== null) {
                $this->orderDetails[] = array(
                    'tableName' => 'InOrderHeader',
                    'before' => MbeSXAdapterHelper::DELETED_ENTITY_METHOD,
                    'after' => $orderData
                );

            } elseif ($oldStatus !== null && !in_array($newStatus, $cancelledOrderStatuses)) {
                $this->orderDetails[] = array(
                    'tableName' => 'InOrderHeader',
                    'before' => MbeSXAdapterHelper::MODIFIED_ENTITY_METHOD,
                    'after' => $orderData
                );
            }
        }
        $this->customerSession->setOrderDetails($this->orderDetails);
    }

    /**
     * @return mixed
     */
    public function getNewOrderStatus()
    {
        return $this->scopeConfig->getValue(
            'mbesx/order_status_method_mapping/new_order_statuses',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getStoreId()
        );
    }

    /**
     * @return mixed
     */
    public function getCancelledOrderStatuses()
    {
        return $this->scopeConfig->getValue(
            'mbesx/order_status_method_mapping/cancelled_order_statuses',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getStoreId()
        );
    }
}