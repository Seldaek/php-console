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

/**
 * Zend_Service_ReCaptcha_MailHide
 *
 * @uses       \Zend\Service\ReCaptcha\ReCaptcha
 * @uses       \Zend\Service\ReCaptcha\MailHideException
 * @uses       \Zend\Validator\EmailAddress
 * @category   Zend
 * @package    Zend_Service
 * @subpackage ReCaptcha
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class MailHide extends ReCaptcha
{
    /**#@+
     * Encryption constants
     */
    const ENCRYPTION_MODE       = MCRYPT_MODE_CBC;
    const ENCRYPTION_CIPHER     = MCRYPT_RIJNDAEL_128;
    const ENCRYPTION_BLOCK_SIZE = 16;
    const ENCRYPTION_IV         = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
    /**#@-*/

    /**
     * Url to the mailhide server
     *
     * @var string
     */
    const MAILHIDE_SERVER = 'http://mailhide.recaptcha.net/d';

    /**
     * The email address to protect
     *
     * @var string
     */
    protected $_email = null;

    /**
     * @var \Zend\Validator\Validator
     */
    protected $_emailValidator;

    /**
     * Binary representation of the private key
     *
     * @var string
     */
    protected $_privateKeyPacked = null;

    /**
     * The local part of the email
     *
     * @var string
     */
    protected $_emailLocalPart = null;

    /**
     * The domain part of the email
     *
     * @var string
     */
    protected $_emailDomainPart = null;

    /**
     * Local constructor
     *
     * @param string $publicKey
     * @param string $privateKey
     * @param string $email
     * @param array|\Zend\Config\Config $options
     */
    public function __construct($publicKey = null, $privateKey = null, $email = null, $options = null)
    {
        /* Require the mcrypt extension to be loaded */
        $this->_requireMcrypt();

        /* If options is a Zend_Config object we want to convert it to an array so we can merge it with the default options */
        if ($options instanceof \Zend\Config\Config) {
            $options = $options->toArray();
        }

        /* Merge if needed */
        if (is_array($options)) {
            $options = array_merge($this->getDefaultOptions(), $options);
        } else {
            $options = $this->getDefaultOptions();
        }

        parent::__construct($publicKey, $privateKey, null, $options);

        if ($email !== null) {
            $this->setEmail($email);
        }
    }


    /**
     * Get emailValidator
     *
     * @return \Zend\Validator\Validator
     */
    public function getEmailValidator()
    {
        if (null === $this->_emailValidator) {
            $this->setEmailValidator(new \Zend\Validator\EmailAddress());
        }
        return $this->_emailValidator;
    }

    /**
     * Set email validator
     *
     * @param  \Zend\Validator\Validator $validator
     * @return \Zend\Service\ReCaptcha\MailHide
     */
    public function setEmailValidator(\Zend\Validator\Validator $validator)
    {
        $this->_emailValidator = $validator;
        return $this;
    }


    /**
     * See if the mcrypt extension is available
     *
     * @throws \Zend\Service\ReCaptcha\MailHideException
     */
    protected function _requireMcrypt()
    {
        if (!extension_loaded('mcrypt')) {
            throw new MailHideException('Use of the Zend\\Service\\ReCaptcha\\MailHide component requires the mcrypt extension to be enabled in PHP');
        }
    }

    /**
     * Serialize as string
     *
     * When the instance is used as a string it will display the email address. Since we can't
     * throw exceptions within this method we will trigger a user warning instead.
     *
     * @return string
     */
    public function __toString()
    {
        try {
            $return = $this->getHtml();
        } catch (\Exception $e) {
            $return = '';
            trigger_error($e->getMessage(), E_USER_WARNING);
        }

        return $return;
    }

    /**
     * Get the default set of parameters
     *
     * @return array
     */
    public function getDefaultOptions()
    {
        return array(
            'encoding'       => 'UTF-8',
            'linkTitle'      => 'Reveal this e-mail address',
            'linkHiddenText' => '...',
            'popupWidth'     => 500,
            'popupHeight'    => 300,
        );
    }

    /**
     * Override the setPrivateKey method
     *
     * Override the parent method to store a binary representation of the private key as well.
     *
     * @param string $privateKey
     * @return \Zend\Service\ReCaptcha\MailHide
     */
    public function setPrivateKey($privateKey)
    {
        parent::setPrivateKey($privateKey);

        /* Pack the private key into a binary string */
        $this->_privateKeyPacked = pack('H*', $this->_privateKey);

        return $this;
    }

    /**
     * Set the email property
     *
     * This method will set the email property along with the local and domain parts
     *
     * @param string $email
     * @return \Zend\Service\ReCaptcha\MailHide
     */
    public function setEmail($email)
    {
        $this->_email = $email;

        $validator = $this->getEmailValidator();
        if (!$validator->isValid($email)) {
            throw new MailHideException('Invalid email address provided');
        }

        $emailParts = explode('@', $email, 2);

        /* Decide on how much of the local part we want to reveal */
        if (strlen($emailParts[0]) <= 4) {
            $emailParts[0] = substr($emailParts[0], 0, 1);
        } else if (strlen($emailParts[0]) <= 6) {
            $emailParts[0] = substr($emailParts[0], 0, 3);
        } else {
            $emailParts[0] = substr($emailParts[0], 0, 4);
        }

        $this->_emailLocalPart = $emailParts[0];
        $this->_emailDomainPart = $emailParts[1];

        return $this;
    }

    /**
     * Get the email property
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->_email;
    }

    /**
     * Get the local part of the email address
     *
     * @return string
     */
    public function getEmailLocalPart()
    {
        return $this->_emailLocalPart;
    }

    /**
     * Get the domain part of the email address
     *
     * @return string
     */
    public function getEmailDomainPart()
    {
        return $this->_emailDomainPart;
    }

    /**
     * Get the HTML code needed for the mail hide
     *
     * @param string $email
     * @return string
     * @throws \Zend\Service\ReCaptcha\MailHideException
     */
    public function getHtml($email = null)
    {
        if ($email !== null) {
            $this->setEmail($email);
        } elseif (null === ($email = $this->getEmail())) {
            throw new MailHideException('Missing email address');
        }

        if ($this->_publicKey === null) {
            throw new MailHideException('Missing public key');
        }

        if ($this->_privateKey === null) {
            throw new MailHideException('Missing private key');
        }

        /* Generate the url */
        $url = $this->_getUrl();

        $enc = $this->getOption('encoding');

        /* Genrate the HTML used to represent the email address */
        $html = htmlentities($this->getEmailLocalPart(), ENT_COMPAT, $enc) 
            . '<a href="' 
                . htmlentities($url, ENT_COMPAT, $enc) 
                . '" onclick="window.open(\'' 
                    . htmlentities($url, ENT_COMPAT, $enc) 
                    . '\', \'\', \'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width='
                    . $this->_options['popupWidth'] 
                    . ',height=' 
                    . $this->_options['popupHeight'] 
                . '\'); return false;" title="' 
                . $this->_options['linkTitle'] 
                . '">' . $this->_options['linkHiddenText'] . '</a>@' 
                . htmlentities($this->getEmailDomainPart(), ENT_COMPAT, $enc);

        return $html;
    }

    /**
     * Get the url used on the "hidden" part of the email address
     *
     * @return string
     */
    protected function _getUrl()
    {
        /* Figure out how much we need to pad the email */
        $numPad = self::ENCRYPTION_BLOCK_SIZE - (strlen($this->_email) % self::ENCRYPTION_BLOCK_SIZE);

        /* Pad the email */
        $emailPadded = str_pad($this->_email, strlen($this->_email) + $numPad, chr($numPad));

        /* Encrypt the email */
        $emailEncrypted = mcrypt_encrypt(self::ENCRYPTION_CIPHER, $this->_privateKeyPacked, $emailPadded, self::ENCRYPTION_MODE, self::ENCRYPTION_IV);

        /* Return the url */
        return self::MAILHIDE_SERVER . '?k=' . $this->_publicKey . '&c=' . strtr(base64_encode($emailEncrypted), '+/', '-_');
    }
}
