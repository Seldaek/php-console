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
 * @package    Zend_Validate
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Validator;

/**
 * @uses       \Zend\Validator\AbstractValidator
 * @uses       \Zend\Validator\Exception
 * @uses       \Zend\Validator\Hostname
 * @category   Zend
 * @package    Zend_Validate
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class EmailAddress extends AbstractValidator
{
    const INVALID            = 'emailAddressInvalid';
    const INVALID_FORMAT     = 'emailAddressInvalidFormat';
    const INVALID_HOSTNAME   = 'emailAddressInvalidHostname';
    const INVALID_MX_RECORD  = 'emailAddressInvalidMxRecord';
    const INVALID_SEGMENT    = 'emailAddressInvalidSegment';
    const DOT_ATOM           = 'emailAddressDotAtom';
    const QUOTED_STRING      = 'emailAddressQuotedString';
    const INVALID_LOCAL_PART = 'emailAddressInvalidLocalPart';
    const LENGTH_EXCEEDED    = 'emailAddressLengthExceeded';

    /**
     * @var array
     */
    protected $_messageTemplates = array(
        self::INVALID            => "Invalid type given. String expected",
        self::INVALID_FORMAT     => "'%value%' is not a valid email address. Use the basic format local-part@hostname",
        self::INVALID_HOSTNAME   => "'%hostname%' is not a valid hostname for email address '%value%'",
        self::INVALID_MX_RECORD  => "'%hostname%' does not appear to have a valid MX record for the email address '%value%'",
        self::INVALID_SEGMENT    => "'%hostname%' is not in a routable network segment. The email address '%value%' should not be resolved from public network",
        self::DOT_ATOM           => "'%localPart%' can not be matched against dot-atom format",
        self::QUOTED_STRING      => "'%localPart%' can not be matched against quoted-string format",
        self::INVALID_LOCAL_PART => "'%localPart%' is not a valid local part for email address '%value%'",
        self::LENGTH_EXCEEDED    => "'%value%' exceeds the allowed length",
    );

    /**
     * @var array
     */
    protected $_messageVariables = array(
        'hostname'  => '_hostname',
        'localPart' => '_localPart'
    );

    /**
     * @var string
     */
    protected $_hostname;

    /**
     * @var string
     */
    protected $_localPart;

    /**
     * Returns the found mx record informations
     *
     * @var array
     */
    protected $_mxRecord;

    /**
     * Internal options array
     */
    protected $options = array(
        'useMxCheck'        => false,
        'useDeepMxCheck'    => false,
        'useDomainCheck'    => true,
        'allow'             => Hostname::ALLOW_DNS,
        'hostnameValidator' => null,
    );

    /**
     * Instantiates hostname validator for local use
     *
     * The following additional option keys are supported:
     * 'hostnameValidator' => A hostname validator, see Zend\Validator\Hostname
     * 'allow'             => Options for the hostname validator, see Zend\Validator\Hostname::ALLOW_*
     * 'useMxCheck'        => If MX check should be enabled, boolean
     * 'useDeepMxCheck'    => If a deep MX check should be done, boolean
     *
     * @param array|\Zend\Config\Config $options OPTIONAL
     * @return void
     */
    public function __construct($options = array())
    {
        if (!is_array($options)) {
            $options = func_get_args();
            $temp['allow'] = array_shift($options);
            if (!empty($options)) {
                $temp['useMxCheck'] = array_shift($options);
            }

            if (!empty($options)) {
                $temp['hostnameValidator'] = array_shift($options);
            }

            $options = $temp;
        }

        if (!array_key_exists('hostnameValidator', $options)) {
            $options['hostnameValidator'] = null;
        }

        parent::__construct($options);
    }

    /**
     * Sets the validation failure message template for a particular key
     * Adds the ability to set messages to the attached hostname validator
     *
     * @param  string $messageString
     * @param  string $messageKey     OPTIONAL
     * @return \Zend\Validator\AbstractValidator Provides a fluent interface
     * @throws \Zend\Validator\Exception
     */
    public function setMessage($messageString, $messageKey = null)
    {
        if ($messageKey === null) {
            $this->options['hostnameValidator']->setMessage($messageString);
            parent::setMessage($messageString);
            return $this;
        }

        if (!isset($this->_messageTemplates[$messageKey])) {
            $this->options['hostnameValidator']->setMessage($messageString, $messageKey);
        } else {
            parent::setMessage($messageString, $messageKey);
        }

        return $this;
    }

    /**
     * Returns the set hostname validator
     *
     * @return \Zend\Validator\Hostname
     */
    public function getHostnameValidator()
    {
        return $this->options['hostnameValidator'];
    }

    /**
     * @param \Zend\Validator\Hostname $hostnameValidator OPTIONAL
     * @return \Zend\Validator\EmailAddress Provides a fluent interface
     */
    public function setHostnameValidator(Hostname $hostnameValidator = null)
    {
        if (!$hostnameValidator) {
            $hostnameValidator = new Hostname($this->getAllow());
        }

        $this->options['hostnameValidator'] = $hostnameValidator;
        return $this;
    }

    /**
     * Returns the allow option of the attached hostname validator
     *
     * @return integer
     */
    public function getAllow()
    {
        return $this->options['allow'];
    }

    /**
     * Sets the allow option of the hostname validator to use
     *
     * @param integer $allow
     * @return \Zend\Validator\EmailAddress Provides a fluent interface
     */
    public function setAllow($allow)
    {
        $this->options['allow'] = $allow;
        if ($this->options['hostnameValidator'] !== null) {
            $this->options['hostnameValidator']->setAllow($allow);
        }

        return $this;
    }

    /**
     * Whether MX checking via getmxrr is supported or not
     *
     * @return boolean
     */
    public function isMxSupported()
    {
        return function_exists('getmxrr');
    }

    /**
     * Returns the set validateMx option
     *
     * @return boolean
     */
    public function getMxCheck()
    {
        return $this->options['useMxCheck'];
    }

    /**
     * Set whether we check for a valid MX record via DNS
     *
     * This only applies when DNS hostnames are validated
     *
     * @param boolean $mx Set allowed to true to validate for MX records, and false to not validate them
     * @return \Zend\Validator\EmailAddress Fluid Interface
     */
    public function useMxCheck($mx)
    {
        $this->options['useMxCheck'] = (bool) $mx;
        return $this;
    }

    /**
     * Returns the set deepMxCheck option
     *
     * @return boolean
     */
    public function getDeepMxCheck()
    {
        return $this->options['useDeepMxCheck'];
    }

    /**
     * Use deep validation for MX records
     *
     * @param boolean $deep Set deep to true to perform a deep validation process for MX records
     * @return \Zend\Validator\EmailAddress Fluid Interface
     */
    public function useDeepMxCheck($deep)
    {
        $this->options['useDeepMxCheck'] = (bool) $deep;
        return $this;
    }

    /**
     * Returns the set domainCheck option
     *
     * @return unknown
     */
    public function getDomainCheck()
    {
        return $this->options['useDomainCheck'];
    }

    /**
     * Sets if the domain should also be checked
     * or only the local part of the email address
     *
     * @param boolean $domain
     * @return \Zend\Validator\EmailAddress Fluid Interface
     */
    public function useDomainCheck($domain = true)
    {
        $this->options['useDomainCheck'] = (boolean) $domain;
        return $this;
    }

    /**
     * Returns if the given host is reserved
     *
     * The following addresses are seen as reserved
     * '0.0.0.0/8', '10.0.0.0/8', '127.0.0.0/8'
     * '172.16.0.0/12'
     * '198.18.0.0/15'
     * '128.0.0.0/16', '169.254.0.0/16', '191.255.0.0/16', '192.168.0.0/16'
     * '192.0.0.0/24', '192.0.2.0/24', '192.88.99.0/24', '198.51.100.0/24', '203.0.113.0/24', '223.255.255.0/24'
     * '224.0.0.0/4', '240.0.0.0/4'
     *
     * @param string $host
     * @return boolean Returns false when minimal one of the given addresses is not reserved
     */
    protected function isReserved($host){
        if (!preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $host)) {
            $host = gethostbynamel($host);
        } else {
            $host = array($host);
        }

        if (empty($host)) {
            return false;
        }

        foreach ($host as $server) {
                 // Search for 0.0.0.0/8, 10.0.0.0/8, 127.0.0.0/8
            if (!preg_match('/^(0|10|127)(\.([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))){3}$/', $server) &&
                // Search for 172.16.0.0.12
                !preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])(\.([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))){2}$/', $server) &&
                // Search for 198.18.0.0/15
                !preg_match('/^198\.(1[8-9])(\.([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))){2}$/', $server) &&
                // Search for 128.0.0.0/16, 169.254.0.0/16, 191.255.0.0/16, 192.168.0.0/16
                !preg_match('/^(128\.0|169\.254|191\.255|192\.168)(\.([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))){2}$/', $server) &&
                // Search for 192.0.0.0/24, 192.0.2.0/24, 192.88.99.0/24, 198.51.100.0/24, 203.0.113.0/24, 223.255.255.0/24
                !preg_match('/^(192\.0\.(0|2)|192\.88\.99|198\.51\.100|203\.0\.113|223\.255\.255)\.([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))$/', $server) &&
                // Search for 224.0.0.0/4, 240.0.0.0/4
                !preg_match('/^(2(2[4-9]|[3-4][0-9]|5[0-5]))(\.([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))){3}$/', $server)) {
                return false;
            }
        }
    }

    /**
     * Internal method to validate the local part of the email address
     *
     * @return boolean
     */
    protected function validateLocalPart()
    {
        // First try to match the local part on the common dot-atom format
        $result = false;

        // Dot-atom characters are: 1*atext *("." 1*atext)
        // atext: ALPHA / DIGIT / and "!", "#", "$", "%", "&", "'", "*",
        //        "+", "-", "/", "=", "?", "^", "_", "`", "{", "|", "}", "~"
        $atext = 'a-zA-Z0-9\x21\x23\x24\x25\x26\x27\x2a\x2b\x2d\x2f\x3d\x3f\x5e\x5f\x60\x7b\x7c\x7d\x7e';
        if (preg_match('/^[' . $atext . ']+(\x2e+[' . $atext . ']+)*$/', $this->_localPart)) {
            $result = true;
        } else {
            // Try quoted string format

            // Quoted-string characters are: DQUOTE *([FWS] qtext/quoted-pair) [FWS] DQUOTE
            // qtext: Non white space controls, and the rest of the US-ASCII characters not
            //   including "\" or the quote character
            $noWsCtl = '\x01-\x08\x0b\x0c\x0e-\x1f\x7f';
            $qtext   = $noWsCtl . '\x21\x23-\x5b\x5d-\x7e';
            $ws      = '\x20\x09';
            if (preg_match('/^\x22([' . $ws . $qtext . '])*[$ws]?\x22$/', $this->_localPart)) {
                $result = true;
            } else {
                $this->error(self::DOT_ATOM);
                $this->error(self::QUOTED_STRING);
                $this->error(self::INVALID_LOCAL_PART);
            }
        }

        return $result;
    }

    /**
     * Returns the found MX Record information after validation including weight for further processing
     *
     * @return array
     */
    public function getMXRecord()
    {
        return $this->_mxRecord;
    }

    /**
     * Internal method to validate the servers MX records
     *
     * @return boolean
     */
    protected function validateMXRecords()
    {
        $mxHosts = array();
        $weight  = array();
        $result = getmxrr($this->_hostname, $mxHosts, $weight);
        if (!empty($mxHosts) && !empty($weight)) {
            $this->_mxRecord = array_combine($mxHosts, $weight);
        } else {
            $this->_mxRecord = $mxHosts;
        }

        arsort($this->_mxRecord);

        if (!$result) {
            $this->error(self::INVALID_MX_RECORD);
        } else if ($this->options['useDeepMxCheck']) {
            $validAddress = false;
            $reserved     = true;
            foreach ($this->_mxRecord as $hostname => $weight) {
                $res = $this->isReserved($hostname);
                if (!$res) {
                    $reserved = false;
                }

                if (!$res
                    && (checkdnsrr($hostname, "A")
                    || checkdnsrr($hostname, "AAAA")
                    || checkdnsrr($hostname, "A6"))) {
                    $validAddress = true;
                    break;
                }
            }

            if (!$validAddress) {
                $result = false;
                if ($reserved) {
                    $this->error(self::INVALID_SEGMENT);
                } else {
                    $this->error(self::INVALID_MX_RECORD);
                }
            }
        }

        return $result;
    }

    /**
     * Internal method to validate the hostname part of the email address
     *
     * @return boolean
     */
    protected function validateHostnamePart()
    {
        $hostname = $this->getHostnameValidator()->setTranslator($this->getTranslator())
                         ->isValid($this->_hostname);
        if (!$hostname) {
            $this->error(self::INVALID_HOSTNAME);
            // Get messages and errors from hostnameValidator
            foreach ($this->getHostnameValidator()->getMessages() as $code => $message) {
                $this->abstractOptions['messages'][$code] = $message;
            }
        } else if ($this->options['useMxCheck']) {
            // MX check on hostname
            $hostname = $this->validateMXRecords();
        }

        return $hostname;
    }

    /**
     * Splits the given value in hostname and local part of the email adress
     *
     * @param string $value Email adress to be split
     * @return bool Returns false when the email can not be split
     */
    protected function splitEmailParts($value)
    {
        // Split email address up and disallow '..'
        if ((strpos($value, '..') !== false) or
            (!preg_match('/^(.+)@([^@]+)$/', $value, $matches))) {
            return false;
        }

        $this->_localPart = $matches[1];
        $this->_hostname  = $matches[2];
        return true;
    }

    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if and only if $value is a valid email address
     * according to RFC2822
     *
     * @link   http://www.ietf.org/rfc/rfc2822.txt RFC2822
     * @link   http://www.columbia.edu/kermit/ascii.html US-ASCII characters
     * @param  string $value
     * @return boolean
     */
    public function isValid($value)
    {
        if (!is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $matches = array();
        $length  = true;
        $this->setValue($value);

        // Split email address up and disallow '..'
        if (!$this->splitEmailParts($value)) {
            $this->error(self::INVALID_FORMAT);
            return false;
        }

        if ((strlen($this->_localPart) > 64) || (strlen($this->_hostname) > 255)) {
            $length = false;
            $this->error(self::LENGTH_EXCEEDED);
        }

        // Match hostname part
        if ($this->options['useDomainCheck']) {
            $hostname = $this->validateHostnamePart();
        }

        $local = $this->validateLocalPart();

        // If both parts valid, return true
        if ($local && $length) {
            if (($this->options['useDomainCheck'] && $hostname) || !$this->options['useDomainCheck']) {
                return true;
            }
        }

        return false;
    }
}
