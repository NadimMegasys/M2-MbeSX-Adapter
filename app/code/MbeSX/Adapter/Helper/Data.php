<?php
namespace MbeSX\Adapter\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Module\Dir\Reader;
use MbeSX\Adapter\Helper\Customer as mbeSXAdapterCustomer;
use MbeSX\Adapter\Helper\MiddlewareConnector as middlewareConnector;
use MbeSX\Adapter\Helper\Notifier as notifier;
use MbeSX\Adapter\Helper\Order as order;
use Magento\Framework\App\Request\Http as request;
use Magento\Framework\Xml\Parser as parser;

/**
 * Helper
 */
class Data extends \MbeSX\Adapter\Helper\CoreHelper
{

    const NEW_ENTITY_METHOD = 1;

    const MODIFIED_ENTITY_METHOD = 2;

    const DELETED_ENTITY_METHOD = 3;

    private $_sxSync = false;

    private $_action = null;

    private $allowedFields = [];

    private $_primaryFields = [];

    private $_processing = '0';

    private $_handling = '0';

    private $_inComparableMethods = array(
        self::NEW_ENTITY_METHOD,
        self::DELETED_ENTITY_METHOD
    );

    private static $configuration = null;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\Module\Dir\Reader
     */
    protected $moduleReader;

    /**
     * @var \MbeSX\Adapter\Helper\Customer
     */
    protected $mbeSXAdapterCustomer;

    /**
     * @var \MbeSX\Adapter\Helper\MiddlewareConnector
     */
    protected $middlewareConnector;

    /**
     * @var \MbeSX\Adapter\Helper\Notifier
     */
    protected $notifier;

    /**
     * @var \MbeSX\Adapter\Helper\Order
     */
    protected $order;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Framework\Xml\Parser
     */
    private $parser;

    /**
     * Data constructor.
     * @param Context $context
     * @param CustomerFactory $customerFactory
     * @param Registry $registry
     * @param Reader $moduleReader
     * @param Customer $mbeSXAdapterCustomer
     * @param \MbeSX\Adapter\Helper\MiddlewareConnector $middlewareConnector
     * @param \MbeSX\Adapter\Helper\Notifier $notifier
     * @param \MbeSX\Adapter\Helper\Order $Order
     * @param request $request
     * @param parser $parser
     */
    public function __construct(
        Context $context,
        CustomerFactory $customerFactory,
        Registry $registry,
        Reader $moduleReader,
        mbeSXAdapterCustomer $mbeSXAdapterCustomer,
        MiddlewareConnector $middlewareConnector,
        notifier $notifier,
        order $Order,
        request $request,
        parser $parser
    ){
        $this->customerFactory = $customerFactory;
        $this->registry = $registry;
        $this->moduleReader = $moduleReader;
        $this->mbeSXAdapterCustomer = $mbeSXAdapterCustomer;
        $this->middlewareConnector = $middlewareConnector;
        $this->notifier = $notifier;
        $this->order = $Order;
        $this->request = $request;
        $this->parser = $parser;
        parent::__construct($context,$parser);
    }


    /**
     * @param $id
     * @return mixed
     */
    public function getCustomerById($id) {
        return $this->customerFactory->create()->load($id);
    }

    /**
     * @param $addressInfo
     * @return mixed
     */
    public function setSalesAddressInfo($addressInfo)
    {
        $salesAddInfo = $this->registry->registry($addressInfo['parent_id']);
        if (! $salesAddInfo) {
            $customer = $this->getCustomerById($addressInfo['parent_id']);
            $salesAddInfo = array(
                'is_default_shipping' => $customer->getDefaultShipping(),
                'is_default_billing' => $customer->getDefaultBilling()
            );
        }
        if ($addressInfo['entity_id'] == $salesAddInfo['is_default_billing']) {
            $addressInfo['is_default_billing'] = '1';
        } else {
            $addressInfo['is_default_billing'] = '0';
        }
        if ($addressInfo['entity_id'] == $salesAddInfo['is_default_shipping']) {
            $addressInfo['is_default_shipping'] = '1';
        } else {
            $addressInfo['is_default_shipping'] = '0';
        }
        return $addressInfo;
    }


    /**
     * @param $allData
     * @param $moduleConfig
     */
    public function customerDataSync($allData,$moduleConfig)
    {
        $apiData = array();
        foreach ($allData as $tableData) {
            $this->_sxSync = false;
            $this->MagentoToMSSQLFields($tableData);
            $whiteListedArr = $this->whiteListing($tableData);
            if (! in_array($tableData['before'], $this->_inComparableMethods)) {
                $this->compare($whiteListedArr, $tableData);
                $this->_action = self::MODIFIED_ENTITY_METHOD;
            } else {
                $this->_sxSync = true;
                $this->_action = $tableData['before'];
            }
            if ($this->_sxSync) {
                if ($tableData['tableName'] == "InCustomer") {
                    $customerData = $this->mbeSXAdapterCustomer->getCustomerInfo($this->allowedFields, $whiteListedArr, $tableData,$moduleConfig);
                } elseif ($tableData['tableName'] == "InCustomerAddress") {
                    $customerData = $this->mbeSXAdapterCustomer->getCustomerAddressInfo($this->allowedFields, $whiteListedArr, $tableData,$moduleConfig);
                }
                $customerData['Method'] = $this->_action;
                $customerData['Processing'] = $this->_processing;

                $apiData[] = array(
                    'tableName' => $tableData['tableName'],
                    'data' => $customerData
                );
            }
        }

        $this->syncToSx($apiData, 'customer');
    }


    /**
     * Order Data Sync
     *
     * @param type $data
     * @param $moduleConfig
     */
    public function orderDataSync($allData,$moduleConfig)
    {
        $apiData = array();
        foreach ($allData as $tableData) {
            $this->_sxSync = false;
            $this->MagentoToMSSQLFields($tableData);

            $whiteListedArr = $this->whiteListing($tableData);
            if (! in_array($tableData['before'], $this->_inComparableMethods)) {
                $this->compare($whiteListedArr, $tableData);
                $this->_action = self::MODIFIED_ENTITY_METHOD;
            } else {
                $this->_sxSync = true;
                $this->_action = $tableData['before'];
            }

            if ($this->_sxSync) {
                if ($tableData['tableName'] == "InOrderHeader") {
                    $orderData = $this->order->getOrderHeaderInfo($this->allowedFields, $whiteListedArr, $tableData,$moduleConfig);
                    $orderData['HandlingCharge'] = $this->_handling;
                } elseif ($tableData['tableName'] == "InOrderLine") {
                    $orderData = $this->order->getOrderLineInfo($this->allowedFields, $tableData,$moduleConfig);
                }
                $orderData['Method'] = $this->_action;
                $orderData['Processing'] = $this->_processing;

                $apiData[] = array(
                    'tableName' => $tableData['tableName'],
                    'data' => $orderData
                );

            }
        }

        $this->syncToSx($apiData, "order");
    }


    /**
     * @param $data
     * @param $entityType
     * @param bool $isExtract
     * @return |null
     */
    public function syncToSx($data, $entityType, $isExtract = false)
    {
        $requestParams = array();
        $tmpArr = array();
        if (sizeof($data) > 0) {
            if(!$isExtract) {
                $tmpArr = $this->formatSyncData($data);
                $data = $this->removeDuplicates($tmpArr);
            }
            $requestParams = array(
                "ChangeLog" => $data,
                "IsExtract" => $isExtract
            );
        } else {
            return null;
        }
        $response = null;

        $syncConfiguration = $this->getConfiguration('mbe-sx-sync');
        try {
            $response = $this->middlewareConnector->Call($syncConfiguration['syncAPI'], $requestParams);
            $this->notifier->syncSuccess($requestParams, $response, $entityType);
        } catch (\Exception $e) {
            $this->notifier->syncError($e, $requestParams, $response, $entityType, $syncConfiguration['syncAPI'],$this->getModuleConfig());
        }
    }

    /**
     * @return array
     */
    public function getModuleConfig()
    {
        $moduleConfig = [];
        $moduleConfig['enable'] = $this->isEnabled();
        $moduleConfig['connector_base_path'] = $this->connectorBasePath();
        $moduleConfig['from_email'] = $this->getFromEmail();
        $moduleConfig['to_email'] = $this->getToEmail();
        $moduleConfig['failure_subject'] = $this->getFailureSubject();

        return $moduleConfig;
    }

    /**
     * @param $data
     * @return array
     */
    private function formatSyncData($data) {
        $formattedData = array();

        foreach ($data as $item) {
            $formattedData[$item['tableName']][] = $item['data'];
        }

        return $formattedData;
    }

    /**
     * Remove duplicates from tabledata
     *
     * @param array $data
     * @return array
     */
    private function removeDuplicates($data)
    {
        $key = 0;
        $index = 0;
        $arrKey = null;
        $uniqueData = array();
        $keyNameMap = array();
        $response = array();

        $tableNameArr = array_keys($data);

        foreach ($data as $tableName) {
            $itemsCount = count($tableName);
            for ($item = 0; $item < $itemsCount; $item ++) {
                $arrKey = $this->formArrKey($tableName[$item], $tableNameArr[$key]);
                $keyNameMap[$tableNameArr[$key]][$arrKey] = $arrKey;

                $uniqueData[$arrKey] = $tableName[$item];
            }
            $key ++;
        }

        foreach ($keyNameMap as $key => $value) {
            foreach ($value as $arrKeys => $arrVals) {
                $response[$index]['tableName'] = $key;
                $response[$index]['data'] = $uniqueData[$arrKeys];
                $index++;
            }
        }
        return $response;
    }

    /**
     * Magento To MSSQL Mapping Fields
     *
     * @param type $tableData
     */
    public function MagentoToMSSQLFields($tableData)
    {
        $moduleDirEtc = $this->moduleReader->getModuleDir(
            \Magento\Framework\Module\Dir::MODULE_ETC_DIR,
            'MbeSX_Adapter'
        );
        $xmlPath = $moduleDirEtc . '/MbeSXMapping.xml';
        $parsedArray = $this->parser->load($xmlPath)->xmlToArray();
        $mappingFields = $parsedArray['config'][$tableData['tableName']];
        foreach ($mappingFields as $key => $node) {
            if ($node['exclude'] !== 'true') {
                $this->allowedFields[$tableData['tableName']]['magento'][$key] = $key;
            }
            $this->allowedFields[$tableData['tableName']]['mssql'][$key] = $node;
        }

        return $this->allowedFields;
    }

    /**
     * WhiteList unmapped fields from table data
     *
     * @param type $tableData
     * @return type
     */
    public function whiteListing($tableData)
    {
        $tableFields = array_keys($tableData['after']);
        $whiteListedArr = array_intersect($this->allowedFields[$tableData['tableName']]['magento'], $tableFields);

        return $whiteListedArr;
    }

    /**
     * Compare
     *
     * @param type $whiteListedArr
     * @param type $tableData
     */
    public function compare($whiteListedArr, $tableData)
    {
        foreach ($whiteListedArr as $field) {
            if ($this->diffCheck($tableData['after'][$field], $tableData['before'][$field])) {
                $this->_sxSync = true;
                break;
            }
        }
    }

    /**
     * Check diff b/w before and after
     *
     * @param type $after
     * @param type $before
     * @return type
     */
    public function diffCheck($after, $before)
    {
        return (strcmp($after, $before) !== 0);
    }

    /**
     * Block the customer address duplicate entry while order with an existing address
     *
     * @param int|string|Array $data
     * @return boolean
     */
    public function blockControllersAndActions($data)
    {
        $controller = $this->request->getControllerName();
        $action = $this->request->getActionName();
        if ($data == 1) {
            return true;
        } else if ($controller == 'sales_order_create' && $action == 'save') {
            return false;
        } else if ($controller == 'index' && $action == 'saveOrder') {
            return false;
        }

        return true;
    }

    /**
     * Form array key with primary identifiers
     *
     * @param array $itemArr
     * @param string $tableName
     * @return string
     */
    private function formArrKey($itemArr, $tableName)
    {
        $arrKeyItem = array();
        $arrKey = null;

        $this->parseMapping($tableName);

        foreach ($this->_primaryFields[$tableName] as $key => $value) {
            $arrKeyItem[] = $value . "_" . $itemArr[$value];
        }

        foreach ($arrKeyItem as $item) {
            $arrKey .= "_" . $item;
        }

        $arrKey = ltrim($arrKey, "_");

        return $arrKey;
    }

    /**
     * Magento To MSSQL Mapping Primary Fields
     *
     * @param type $tableName
     */
    public function parseMapping($tableName)
    {
        $moduleDirEtc = $this->moduleReader->getModuleDir(
            \Magento\Framework\Module\Dir::MODULE_ETC_DIR,
            'MbeSX_Adapter'
        );
        $xmlPath = $moduleDirEtc . '/MbeSXMapping.xml';
        $parsedArray = $this->parser->load($xmlPath)->xmlToArray();

        if (isset($parsedArray['config'][$tableName])) {
            $mappingFields = $parsedArray['config'][$tableName];
            foreach ($mappingFields as $key => $node) {
                if ((isset($node['identifier'])) && ($node['identifier'] === 'true')) {
                    $this->_primaryFields[$tableName][$node['map']] = $node['map'];
                }
            }
        }
        return $this->_primaryFields;
    }

}
