<?php
namespace MbeSX\Adapter\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface as ScopeInterface;
use Magento\Framework\Xml\Parser as parser;

/**
 * Helper
 */
class CoreHelper extends AbstractHelper
{
    private static $configuration = null;

    /**
     * Enable Module from system config
     */
    const XML_PATH_MBESX_ADAPTER_IS_ENABLED = 'mbesx/site_options/enable_push';


    /**
     * Site Id from system config
     */
    const XML_PATH_MBESX_SITE_ID = 'mbesx/site_options/site_id';

    /**
     * Base Path from system config
     */
    const XML_PATH_MBESX_BASE_URL = 'mbesx/middleware_options/base_url';


    /**
     * Authorization from system config
     */
    const XML_PATH_MBESX_AUTHORIZATION = 'mbesx/middleware_options/authorization';

    /**
     * Form Email from system config
     */
    const XML_PATH_MBESX_FROM_EMAIL = 'mbesx/email_options/from_email';

    /**
     * To Email from system config
     */
    const XML_PATH_MBESX_TO_EMAIL = 'mbesx/email_options/to_email';

    /**
     * To Failure Subject system config
     */
    const XML_PATH_MBESX_FAILURE_SUBJECT = 'mbesx/email_options/failure_subject';

    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Xml\Parser
     */
    private $parser;

    /**
     * Data constructor.
     * @param Context $context
     * @param parser $parser
     */
    public function __construct(
        Context $context,
        parser $parser
    ){
        parent::__construct($context);
        $this->scopeConfig  = $context->getScopeConfig();
        $this->parser = $parser;
    }

    /**
     * Get config
     * @param $configPath
     * @param null $store
     * @return mixed
     */
    public function getGeneralConfig($configPath, $store = null)
    {
        return $this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check weather module is enable or disable
     * @param null $store
     * @return bool
     */
    public function isEnabled($store = null)
    {
        return $this->getGeneralConfig(self::XML_PATH_MBESX_ADAPTER_IS_ENABLED, $store);
    }

    /**
     * get Site Id
     * @param null $store
     * @return bool
     */
    public function getSiteID($store = null)
    {
        return $this->getGeneralConfig(self::XML_PATH_MBESX_SITE_ID, $store);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function connectorBasePath($store = null)
    {
        return $this->getGeneralConfig(self::XML_PATH_MBESX_BASE_URL, $store);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getAuthorization($store = null)
    {
        return $this->getGeneralConfig(self::XML_PATH_MBESX_AUTHORIZATION, $store);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getFromEmail($store = null)
    {
        return $this->getGeneralConfig(self::XML_PATH_MBESX_FROM_EMAIL, $store);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getToEmail($store = null)
    {
        return $this->getGeneralConfig(self::XML_PATH_MBESX_TO_EMAIL, $store);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getFailureSubject($store = null)
    {
        return $this->getGeneralConfig(self::XML_PATH_MBESX_FAILURE_SUBJECT, $store);
    }

    /**
     * @param null $section
     * @return array
     */
    protected function getConfiguration($section = null)
    {
        $this->processConfigFile();
        if ($section === null) {
            return self::$configuration['common'];
        } elseif (! empty(self::$configuration[$section]) && self::$configuration[$section]) {
            return array_merge(self::$configuration[$section], self::$configuration['common']);
        }
        return array();
    }

    /**
     * processConfigFile
     */
    private function processConfigFile()
    {
        if (self::$configuration === null) {

            $moduleDirEtc = $this->moduleReader->getModuleDir(
                \Magento\Framework\Module\Dir::MODULE_ETC_DIR,
                'MbeSX_Adapter'
            );
            $configFilePath = $moduleDirEtc . '/config.xml';
            $configFile = $this->parser->load($configFilePath)->xmlToArray();
            self::$configuration = $configFile['config']['_value'];
        }
    }

    /**
     * Set the expected datatype to the value
     *
     * @param type $value string|int|datetime
     * @param type $dataType string
     * @return type string|int|datetime
     */
    public function setDataType($value, $dataType)
    {
        switch ($dataType) {
            case "datetime":
                $value = ! empty($value) ? date("Y-m-d H:i:s", strtotime($value)) : '';
                break;
            case "serialize":
                $value = (string) serialize($value);
                break;
            case "integer":
                $value = (int) $value;
                break;
            case "float":
                $value = (float) $value;
                break;
            case "string":
                $value = (string) $value;
                break;
        }

        return $value;
    }
}
