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

use Traversable,
    Zend\Stdlib\IteratorToArray;

/**
 * @uses       \Zend\Validator\AbstractValidator
 * @category   Zend
 * @package    Zend_Validate
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Identical extends AbstractValidator
{
    /**
     * Error codes
     * @const string
     */
    const NOT_SAME      = 'notSame';
    const MISSING_TOKEN = 'missingToken';

    /**
     * Error messages
     * @var array
     */
    protected $_messageTemplates = array(
        self::NOT_SAME      => "The two given tokens do not match",
        self::MISSING_TOKEN => 'No token was provided to match against',
    );

    /**
     * @var array
     */
    protected $_messageVariables = array(
        'token' => 'tokenString'
    );

    /**
     * Original token against which to validate
     * @var string
     */
    protected $tokenString;
    protected $token;
    protected $strict = true;

    /**
     * Sets validator options
     *
     * @param  mixed $token
     * @return void
     */
    public function __construct($token = null)
    {
        if ($token instanceof Traversable) {
            $token = IteratorToArray::convert($token);
        }

        if (is_array($token) && array_key_exists('token', $token)) {
            if (array_key_exists('strict', $token)) {
                $this->setStrict($token['strict']);
            }

            $this->setToken($token['token']);
        } elseif (null !== $token) {
            $this->setToken($token);
        }
        
        parent::__construct(is_array($token) ? $token : null);
    }

    /**
     * Retrieve token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set token against which to compare
     *
     * @param  mixed $token
     * @return \Zend\Validator\Identical
     */
    public function setToken($token)
    {
        $this->tokenString = (string) $token;
        $this->token       = $token;
        return $this;
    }

    /**
     * Returns the strict parameter
     *
     * @return boolean
     */
    public function getStrict()
    {
        return $this->strict;
    }

    /**
     * Sets the strict parameter
     *
     * @param Zend\Validator\Identical
     */
    public function setStrict($strict)
    {
        $this->strict = (boolean) $strict;
        return $this;
    }

    /**
     * Returns true if and only if a token has been set and the provided value
     * matches that token.
     *
     * @param  mixed $value
     * @param  array $context
     * @return boolean
     */
    public function isValid($value, $context = null)
    {
        $this->setValue((string) $value);

        if (($context !== null) && isset($context) && array_key_exists($this->getToken(), $context)) {
            $token = $context[$this->getToken()];
        } else {
            $token = $this->getToken();
        }

        if ($token === null) {
            $this->error(self::MISSING_TOKEN);
            return false;
        }

        $strict = $this->getStrict();
        if (($strict && ($value !== $token)) || (!$strict && ($value != $token))) {
            $this->error(self::NOT_SAME);
            return false;
        }

        return true;
    }
}
