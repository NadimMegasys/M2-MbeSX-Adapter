<?php

namespace MbeSX\Adapter\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Math\Random;
use Magento\Framework\Mail\Template\TransportBuilder as TransportBuilder;
use Magento\Framework\App\Area as Area;

/**
 * Handles the notification for the MbeSX Module
 *
 * @author vignesh.sakthivel
 */
class Notifier extends \MbeSX\Adapter\Helper\CoreHelper
{

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directorylist;


    /**
     * @var Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Magento\Framework\Math\Random
     */
    private $mathRandom;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * Notifier constructor.
     * @param DirectoryList $directorylist
     * @param StoreManagerInterface $storeManager
     * @param Random $mathRandom
     * @param TransportBuilder $transportBuilder
     */
    public function __construct(
        DirectoryList $directorylist,
        StoreManagerInterface $storeManager,
        Random $mathRandom,
        TransportBuilder $transportBuilder
    )
    {
        $this->directorylist = $directorylist;
        $this->storeManager = $storeManager;
        $this->mathRandom = $mathRandom;
        $this->transportBuilder = $transportBuilder;
    }

    /**
     * Logs the request and response on successful push of the
     * changes to middleware
     *
     * @param array|mixed $request
     * @param array|mixed $response
     * @param String $entityType
     */
    public function syncSuccess($request, $response, $entityType)
    {
        $logMessage = sprintf("%s: Changes made:\r\n%s and pushed to SX. Response from SX:\r\n%s", ucfirst($entityType), json_encode($request), json_encode($response));
        $this->log($logMessage, $this->getLogFile($entityType));
    }

    /**
     * Logs the request and response on failured of push to the changes to middleware
     * Exception will be logged in a separate file.
     * Triggers email to the administrator about the failure
     *
     * @param \Exception $e
     * @param array|mixed $request
     * @param array|mixed $response
     * @param String $entityType
     * @param array|mixed $endPoint
     * @param array $moduleConfig
     */
    public function syncError(\Exception $e, $request, $response, $entityType, $endPoint, $moduleConfig)
    {
        $errorLogFile = $this->getErrorLogFile();
        $this->log($e->__toString(), $errorLogFile);


        $logMessage = sprintf("%s: Changes made:\r\n
                Request : \r\n%s\r\n
                Pushing to MbeSXMiddleware failed. Refer the attached ErrorTrace.log file for more details \r\n
                URL : %s \r\n
                Location of the log in Server: %s",
            ucfirst($entityType),
            json_encode($request),
            $moduleConfig['connector_base_path'] . $endPoint,
            $this->getBaseLogDir() . $errorLogFile);

        $this->log($logMessage, $this->getLogFile($entityType));
        $requestLogFile = $this->getErrorLogFile();
        $this->log($logMessage, $requestLogFile);
        $this->notifyAdmin($this->getBaseLogDir() . $errorLogFile, $this->getBaseLogDir() . $requestLogFile, $moduleConfig);
    }

    /**
     * Gives the log file path for the given entityType
     *
     * @param string $entityType
     * @return string
     */
    protected function getLogFile($entityType)
    {
        $logFilePath = sprintf("%s/%s.log", $this->getLogDir(), $entityType);
        return substr($logFilePath, strlen($this->getBaseLogDir()));
    }

    /**
     * Generates random error log file path
     *
     * @return string
     */
    protected function getErrorLogFile()
    {
        $random = $this->mathRandom->getRandomString(16);

        $logFilePath = sprintf("%s/%s.log", $this->getLogDir(true), $random);
        return substr($logFilePath, strlen($this->getBaseLogDir()));
    }

    /**
     * Creates log dir for the given hour and returns the path
     * of the log dir
     *
     * @param boolean $exception
     * @return string
     */
    protected function getLogDir($exception = false)
    {
        $ds = '/';
        $sxSyncLogDir = $this->getBaseLogDir() . sprintf("sx-sync{$ds}%s", date("Y{$ds}m{$ds}d"));
        if ($exception) {
            $sxSyncLogDir .= '/' . "exceptions";
        }

        if (!file_exists($sxSyncLogDir)) {
            mkdir($sxSyncLogDir, 0777, true);
            chmod($sxSyncLogDir, 0777);
        }

        return $sxSyncLogDir;
    }

    /**
     * Notifies the failure to Admin
     *
     * @param string $logFile
     */
    private function mailingMsg($baseUrl, $logFile)
    {
        $message = <<<MSG
Hi,
<br />
<br />
E-Commerce push changes failed in {$baseUrl}.
<br />
<br />
Refer {$logFile} for more details
<br />
<br />
MSG;

        return $message;
    }

    protected function _addAttachments($mailTemplate, $logFile, $attachmentFile)
    {
        // add attachment
        $mailTemplate->createAttachment(
            file_get_contents($logFile), //location of file
            Zend_Mime::TYPE_OCTETSTREAM,
            Zend_Mime::DISPOSITION_ATTACHMENT,
            Zend_Mime::ENCODING_BASE64,
            $attachmentFile
        );

        return $mailTemplate;
    }


    public function notifyAdmin($logFile, $requestLogFile, $moduleConfig)
    {

        try {
            $storeId = $this->storeManager->getStore()->getId();
            $baseUrl = $this->storeManager->getStore()->getBaseUrl();
            $failureSubject = sprintf($moduleConfig['failure_subject'], $baseUrl);
            $emailVariables = [
                'failure_subject' => $failureSubject,
                'base_url' => $baseUrl,
                'logfile' => $logFile
            ];

            $transport = $this->transportBuilder
                ->setTemplateIdentifier('mbesx_error_notifier')
                ->setTemplateOptions(
                    [
                        'area' => Area::AREA_FRONTEND,
                        'store' => $storeId
                    ]
                )
                ->setFrom(
                    [
                        'email' => $moduleConfig['from_email'],
                        'name' => 'Mbe SX - Magento'
                    ]
                )
                ->setTemplateVars($emailVariables)
                ->addTo($moduleConfig['to_email'])
                ->getTransport();
            $transport->sendMessage();
        } catch (Exception $e) {

        }
    }

    /**
     * Returns the application log dir path
     *
     * @return string
     */
    private function getBaseLogDir()
    {
        return $this->directorylist->getPath('var') . '/' . 'log/';
    }

    /**
     * To create a Magento log
     * @param $message
     */
    public function log($message, $fileName)
    {
        //\Zend\Log\Logger::INFO

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/' . $fileName);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($message);
    }// end log()
}