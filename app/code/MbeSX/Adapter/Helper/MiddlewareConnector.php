<?php
namespace MbeSX\Adapter\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use MbeSX\Adapter\Helper\Data as mbeSXAdapterHelper;
/**
 * Class for managing the Curl Requests to the Middleware Connector
 * @author vignesh.sakthivel
 */
class MiddlewareConnector extends \MbeSX\Adapter\Helper\CoreHelper
{

    /**
     * Curl Handler
     *
     * @var cURL Resource
     */
    protected $_curlHandle = null;

    /**
     * Connection Time Out
     *
     * @var integer
     */
    protected $_connectionTimeOut = 30;

    /**
     * Total Time Out
     *
     * @var integer
     */
    protected $_timeOut = 30;

    /**
     * Authorization Token of the Middleware API
     *
     * @var string
     */
    protected $_authorization = null;

    /**
     * Content Type of the Request to the Middleware API
     *
     * @var string
     */
    protected $_contentType = 'application/json';

    /**
     * Headers of the Request to the Middleware API
     *
     * @var array
     */
    protected $_headers = array();

    /**
     * @var MbeSX\Adapter\Helper\Data
     */
    private $mbeSXAdapterHelper;

    /**
     * Executes cURL call to the Middleware API
     *
     * @param String URL Path
     * @param mixed $request
     * @throws Exception in case of any connection issues
     *
     * @return mixed response
     */
    public function Call($apiURL, $postParams = null)
    {
        $configuration = $this->getConfiguration('middleware-connect');
        $this->_initializeCurlHandler($configuration);
        $response = null;
        try {

            curl_setopt($this->_curlHandle, CURLOPT_URL, $this->connectorBasePath() . $apiURL);

            if ($postParams) {
                curl_setopt($this->_curlHandle, CURLOPT_POSTFIELDS, $this->_processRequest($postParams));
            }
            $response = curl_exec($this->_curlHandle);
            $httpCode = curl_getinfo($this->_curlHandle, CURLINFO_HTTP_CODE);
            if ($httpCode === 404) {
                throw new \Exception('Middleware API Not found', 1010);
            } else
                if (! $response) {
                    throw new \Exception(
                        __(curl_error($this->_curlHandle), curl_errno($this->_curlHandle))
                    );

                } else {
                    $result = $this->_processResponse($response);
                    if (is_object($result) && empty($result->Success)) {
                        throw new \Exception('Middleware API Response Failed', 1010);
                    } elseif (is_array($result) && empty($result['Success'])) {
                        throw new \Exception('Middleware API Response Failed', 1010);
                    }
                }
        } catch (\Exception $e) {
            throw new \Exception('Call to Middleware API Failed: @' . "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
        }

        return $this->_processResponse($response);
    }

    /**
     * Initializes the cURL resource
     *
     * @param array $configuration
     * @return MbeSX_Adapter_Helper_MiddlewareConnector
     */
    protected function _initializeCurlHandler($configuration)
    {
        $this->_processConfiguration($configuration);
        if ($this->_curlHandle === null) {
            $this->_curlHandle = curl_init();
            curl_setopt($this->_curlHandle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($this->_curlHandle, CURLOPT_CONNECTTIMEOUT, $this->_connectionTimeOut);
            curl_setopt($this->_curlHandle, CURLOPT_TIMEOUT, $this->_timeOut);
            curl_setopt($this->_curlHandle, CURLOPT_HTTPHEADER, $this->_headers);
        }

        return $this;
    }

    /**
     * Processes the configuration and sets it to the property
     *
     * @param array $configuration
     * @return MbeSX_Adapter_Helper_MiddlewareConnector
     */
    private function _processConfiguration($configuration)
    {
        foreach ($configuration as $configProperty => $configValue) {
            $propertyName = "_{$configProperty}";
            if (property_exists($this, $propertyName)) {
                $this->{$propertyName} = $configValue;
            }
        }

        return $this->_setHeaders();
    }

    /**
     * Processes the configuration and sets the header of the APi Request
     *
     * @param array $configuration
     * @return MbeSX_Adapter_Helper_MiddlewareConnector
     */
    private function _setHeaders()
    {
        if ($this->_contentType) {
            $this->_headers[] = 'Content-Type: ' . $this->_contentType;
        }

        if ($this->_contentType) {
            $this->_headers[] = 'Authorization: ' . $this->getAuthorization();
        }

        return $this;
    }

    /**
     * Method to convert the response to PHP array based on the content type
     *
     * @return Array Response
     */
    private function _processResponse($response)
    {
        if ($this->_contentType === 'application/json') {
            return json_decode($response);
        } else {
            return $response;
        }
    }

    /**
     * Method to form the request body based on the content type
     *
     * @return String Request Body
     */
    private function _processRequest($request)
    {
        if ($this->_contentType === 'application/json') {
            return json_encode($request);
        } else {
            return $request;
        }
    }
}