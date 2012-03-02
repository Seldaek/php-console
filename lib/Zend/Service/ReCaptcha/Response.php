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
 * @package    Zend_Service
 * @subpackage ReCaptcha
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Service\ReCaptcha;

use Zend\Http\Response as HTTPResponse;

/**
 * Zend_Service_ReCaptcha_Response
 *
 * @category   Zend
 * @package    Zend_Service
 * @subpackage ReCaptcha
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Response
{
    /**
     * Status
     *
     * true if the response is valid or false otherwise
     *
     * @var boolean
     */
    protected $_status = null;

    /**
     * Error code
     *
     * The error code if the status is false. The different error codes can be found in the
     * recaptcha API docs.
     *
     * @var string
     */
    protected $_errorCode = null;

    /**
     * Class constructor used to construct a response
     *
     * @param string $status
     * @param string $errorCode
     * @param \Zend\Http\Response $httpResponse If this is set the content will override $status and $errorCode
     */
    public function __construct($status = null, $errorCode = null, HTTPResponse $httpResponse = null)
    {
        if ($status !== null) {
            $this->setStatus($status);
        }

        if ($errorCode !== null) {
            $this->setErrorCode($errorCode);
        }

        if ($httpResponse !== null) {
            $this->setFromHttpResponse($httpResponse);
        }
    }

    /**
     * Set the status
     *
     * @param string $status
     * @return \Zend\Service\ReCaptcha\Response
     */
    public function setStatus($status)
    {
        if ($status === 'true') {
            $this->_status = true;
        } else {
            $this->_status = false;
        }

        return $this;
    }

    /**
     * Get the status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * Alias for getStatus()
     *
     * @return boolean
     */
    public function isValid()
    {
        return $this->getStatus();
    }

    /**
     * Set the error code
     *
     * @param string $errorCode
     * @return \Zend\Service\ReCaptcha\Response
     */
    public function setErrorCode($errorCode)
    {
        $this->_errorCode = $errorCode;

        return $this;
    }

    /**
     * Get the error code
     *
     * @return string
     */
    public function getErrorCode()
    {
        return $this->_errorCode;
    }

    /**
     * Populate this instance based on a Zend_Http_Response object
     *
     * @param \Zend\Http\Response $response
     * @return \Zend\Service\ReCaptcha\Response
     */
    public function setFromHttpResponse(HTTPResponse $response)
    {
        $body = $response->getBody();

        $parts = explode("\n", $body, 2);

        if (count($parts) !== 2) {
            $status = 'false';
            $errorCode = '';
        } else {
            list($status, $errorCode) = $parts;
        }

        $this->setStatus($status);
        $this->setErrorCode($errorCode);

        return $this;
    }
}
