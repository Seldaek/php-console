<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Service_Amazon
 * @subpackage Sqs
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Service\Amazon\Sqs;
use Zend\Service\Amazon,
    Zend\Service\Amazon\Sqs\Exception,
    Zend\Crypt;

/**
 * Class for connecting to the Amazon Simple Queue Service (SQS)
 *
 * @uses       SimpleXMLElement
 * @uses       Zend_Crypt_Hmac
 * @uses       Zend_Service_Amazon_Abstract
 * @uses       \Zend\Service\Amazon\Sqs\Exception
 * @category   Zend
 * @package    Zend_Service
 * @subpackage Amazon_Sqs
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @see        http://aws.amazon.com/sqs/ Amazon Simple Queue Service
 */
class Sqs extends \Zend\Service\Amazon\AbstractAmazon
{
    /**
     * Default timeout for createQueue() function
     */
    const CREATE_TIMEOUT_DEFAULT = 30;

    /**
     * HTTP end point for the Amazon SQS service
     */
    protected $_sqsEndpoint = 'queue.amazonaws.com';

    /**
     * The API version to use
     */
    protected $_sqsApiVersion = '2009-02-01';

    /**
     * Signature Version
     */
    protected $_sqsSignatureVersion = '2';

    /**
     * Signature Encoding Method
     */
    protected $_sqsSignatureMethod = 'HmacSHA256';

    /**
     * Constructor
     *
     * @param string $accessKey
     * @param string $secretKey
     * @param string $region
     */
    public function __construct($accessKey = null, $secretKey = null, $region = null)
    {
        parent::__construct($accessKey, $secretKey, $region);
    }

    /**
     * Create a new queue
     *
     * Visibility timeout is how long a message is left in the queue "invisible"
     * to other readers.  If the message is acknowleged (deleted) before the
     * timeout, then the message is deleted.  However, if the timeout expires
     * then the message will be made available to other queue readers.
     *
     * @param  string  $queue_name queue name
     * @param  integer $timeout    default visibility timeout
     * @return string|boolean
     * @throws Zend\Service\Amazon\Sqs\Exception
     */
    public function create($queue_name, $timeout = null)
    {
        $params = array();
        $params['QueueName'] = $queue_name;
        $timeout = ($timeout === null) ? self::CREATE_TIMEOUT_DEFAULT : (int)$timeout;
        $params['DefaultVisibilityTimeout'] = $timeout;

        $retry_count = 0;

        do {
            $retry  = false;
            $result = $this->_makeRequest(null, 'CreateQueue', $params);

            if ($result->CreateQueueResult->QueueUrl === null) {
                if ($result->Error->Code == 'AWS.SimpleQueueService.QueueNameExists') {
                    return false;
                } elseif ($result->Error->Code == 'AWS.SimpleQueueService.QueueDeletedRecently') {
                    // Must sleep for 60 seconds, then try re-creating the queue
                    sleep(60);
                    $retry = true;
                    $retry_count++;
                } else {
                    throw new Exception\RuntimeException($result->Error->Code);
                }
            } else {
                return (string) $result->CreateQueueResult->QueueUrl;
            }

        } while ($retry);

        return false;
    }

    /**
     * Delete a queue and all of it's messages
     *
     * Returns false if the queue is not found, true if the queue exists
     *
     * @param  string  $queue_url queue URL
     * @return boolean
     * @throws Zend\Service\Amazon\Sqs\Exception
     */
    public function delete($queue_url)
    {
        $result = $this->_makeRequest($queue_url, 'DeleteQueue');

        if ($result->Error->Code !== null) {
            throw new Exception\RuntimeException($result->Error->Code);
        }

        return true;
    }

    /**
     * Get an array of all available queues
     *
     * @return array
     * @throws Zend\Service\Amazon\Sqs\Exception
     */
    public function getQueues()
    {
        $result = $this->_makeRequest(null, 'ListQueues');

        if ($result->ListQueuesResult->QueueUrl === null) {
            throw new Exception\RuntimeException($result->Error->Code);
        }

        $queues = array();
        foreach ($result->ListQueuesResult->QueueUrl as $queue_url) {
            $queues[] = (string)$queue_url;
        }

        return $queues;
    }

    /**
     * Return the approximate number of messages in the queue
     *
     * @param  string  $queue_url Queue URL
     * @return integer
     * @throws Zend\Service\Amazon\Sqs\Exception
     */
    public function count($queue_url)
    {
        return (int)$this->getAttribute($queue_url, 'ApproximateNumberOfMessages');
    }

    /**
     * Send a message to the queue
     *
     * @param  string $queue_url Queue URL
     * @param  string $message   Message to send to the queue
     * @return string            Message ID
     * @throws Zend\Service\Amazon\Sqs\Exception
     */
    public function send($queue_url, $message)
    {
        $params = array();
        $params['MessageBody'] = urlencode($message);

        $checksum = md5($params['MessageBody']);

        $result = $this->_makeRequest($queue_url, 'SendMessage', $params);

        if ($result->SendMessageResult->MessageId === null) {
            throw new Exception\RuntimeException($result->Error->Code);
        } else if ((string) $result->SendMessageResult->MD5OfMessageBody != $checksum) {
            throw new Exception\RuntimeException('MD5 of body does not match message sent');
        }

        return (string) $result->SendMessageResult->MessageId;
    }

    /**
     * Get messages in the queue
     *
     * @param  string  $queue_url    Queue name
     * @param  integer $max_messages Maximum number of messages to return
     * @param  integer $timeout      Visibility timeout for these messages
     * @return array
     * @throws Zend\Service\Amazon\Sqs\Exception
     */
    public function receive($queue_url, $max_messages = null, $timeout = null)
    {
        $params = array();

        // If not set, the visibility timeout on the queue is used
        if ($timeout !== null) {
            $params['VisibilityTimeout'] = (int)$timeout;
        }

        // SQS will default to only returning one message
        if ($max_messages !== null) {
            $params['MaxNumberOfMessages'] = (int)$max_messages;
        }

        $result = $this->_makeRequest($queue_url, 'ReceiveMessage', $params);

        if ($result->ReceiveMessageResult->Message === null) {
            throw new Exception\RuntimeException($result->Error->Code);
        }

        $data = array();
        foreach ($result->ReceiveMessageResult->Message as $message) {
            $data[] = array(
                'message_id' => (string)$message->MessageId,
                'handle'     => (string)$message->ReceiptHandle,
                'md5'        => (string)$message->MD5OfBody,
                'body'       => urldecode((string)$message->Body),
            );
        }

        return $data;
    }

    /**
     * Delete a message from the queue
     *
     * Returns true if the message is deleted, false if the deletion is
     * unsuccessful.
     *
     * @param  string $queue_url  Queue URL
     * @param  string $handle     Message handle as returned by SQS
     * @return boolean
     * @throws Zend\Service\Amazon\Sqs\Exception
     */
    public function deleteMessage($queue_url, $handle)
    {
        $params = array();
        $params['ReceiptHandle'] = (string)$handle;

        $result = $this->_makeRequest($queue_url, 'DeleteMessage', $params);

        if ($result->Error->Code !== null) {
            return false;
        }

        // Will always return true unless ReceiptHandle is malformed
        return true;
    }

    /**
     * Get the attributes for the queue
     *
     * @param  string $queue_url  Queue URL
     * @param  string $attribute
     * @return string
     * @throws Zend\Service\Amazon\Sqs\Exception
     */
    public function getAttribute($queue_url, $attribute = 'All')
    {
        $params = array();
        $params['AttributeName'] = $attribute;

        $result = $this->_makeRequest($queue_url, 'GetQueueAttributes', $params);

        if ($result->GetQueueAttributesResult->Attribute === null) {
            throw new Exception\RuntimeException($result->Error->Code);
        }
        
        if(count($result->GetQueueAttributesResult->Attribute) > 1) {
            $attr_result = array();
            foreach($result->GetQueueAttributesResult->Attribute as $attribute) {
                $attr_result[(string)$attribute->Name] = (string)$attribute->Value;
            }
            return $attr_result;
        } else {
            return (string) $result->GetQueueAttributesResult->Attribute->Value;
        }
    }

    /**
     * Make a request to Amazon SQS
     *
     * @param  string           $queue  Queue Name
     * @param  string           $action SQS action
     * @param  array            $params
     * @return SimpleXMLElement
     */
    private function _makeRequest($queue_url, $action, $params = array())
    {
        $params['Action'] = $action;
        $params = $this->addRequiredParameters($queue_url, $params);

        if ($queue_url === null) {
            $queue_url = '/';
        }

        $client = $this->getHttpClient();

        switch ($action) {
            case 'ListQueues':
            case 'CreateQueue':
                $client->setUri('http://'.$this->_sqsEndpoint);
                break;
            default:
                $client->setUri($queue_url);
                break;
        }

        $retry_count = 0;

        do {
            $retry = false;

            $client->resetParameters();
            $client->setParameterGet($params);

            $response = $client->request('GET');

            $response_code = $response->getStatus();

            // Some 5xx errors are expected, so retry automatically
            if ($response_code >= 500 && $response_code < 600 && $retry_count <= 5) {
                $retry = true;
                $retry_count++;
                sleep($retry_count / 4 * $retry_count);
            }
        } while ($retry);

        unset($client);

        return new \SimpleXMLElement($response->getBody());
    }

    /**
     * Adds required authentication and version parameters to an array of
     * parameters
     *
     * The required parameters are:
     * - AWSAccessKey
     * - SignatureVersion
     * - Timestamp
     * - Version and
     * - Signature
     *
     * If a required parameter is already set in the <tt>$parameters</tt> array,
     * it is overwritten.
     *
     * @param  string $queue_url  Queue URL
     * @param  array  $parameters the array to which to add the required
     *                            parameters.
     * @return array
     */
    protected function addRequiredParameters($queue_url, array $parameters)
    {
        $parameters['AWSAccessKeyId']   = $this->_getAccessKey();
        $parameters['SignatureVersion'] = $this->_sqsSignatureVersion;
        $parameters['Timestamp']        = gmdate('Y-m-d\TH:i:s\Z', time()+10);
        $parameters['Version']          = $this->_sqsApiVersion;
        $parameters['SignatureMethod']  = $this->_sqsSignatureMethod;
        $parameters['Signature']        = $this->_signParameters($queue_url, $parameters);

        return $parameters;
    }

    /**
     * Computes the RFC 2104-compliant HMAC signature for request parameters
     *
     * This implements the Amazon Web Services signature, as per the following
     * specification:
     *
     * 1. Sort all request parameters (including <tt>SignatureVersion</tt> and
     *    excluding <tt>Signature</tt>, the value of which is being created),
     *    ignoring case.
     *
     * 2. Iterate over the sorted list and append the parameter name (in its
     *    original case) and then its value. Do not URL-encode the parameter
     *    values before constructing this string. Do not use any separator
     *    characters when appending strings.
     *
     * @param  string $queue_url  Queue URL
     * @param  array  $parameters the parameters for which to get the signature.
     *
     * @return string the signed data.
     */
    protected function _signParameters($queue_url, array $paramaters)
    {
        $data = "GET\n";
        $data .= $this->_sqsEndpoint . "\n";
        if ($queue_url !== null) {
            $data .= parse_url($queue_url, PHP_URL_PATH);
        }
        else {
            $data .= '/';
        }
        $data .= "\n";

        uksort($paramaters, 'strcmp');
        unset($paramaters['Signature']);

        $arrData = array();
        foreach($paramaters as $key => $value) {
            $arrData[] = $key . '=' . str_replace('%7E', '~', urlencode($value));
        }

        $data .= implode('&', $arrData);

        $hmac = Crypt\Hmac::compute($this->_getSecretKey(), 'SHA256', $data, Crypt\Hmac::BINARY);

        return base64_encode($hmac);
    }
}
