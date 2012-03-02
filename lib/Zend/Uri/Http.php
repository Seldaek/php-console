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
 * @category  Zend
 * @package   Zend_Uri
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Uri;

/**
 * HTTP URI handler
 *
 * @category  Zend
 * @package   Zend_Uri
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd     New BSD License
 */
class Http extends Uri
{
    /**
     * @see Uri::$validSchemes
     */
    protected static $validSchemes = array('http', 'https');

    /**
     * @see Uri::$defaultPorts
     */
    protected static $defaultPorts = array(
        'http'  => 80,
        'https' => 443,
    );

    /**
     * @see Uri::$validHostTypes
     */
    protected $validHostTypes = self::HOST_DNSORIPV4;

    /**
     * User name as provided in authority of URI
     * @var null|string
     */
    protected $user;

    /**
     * Password as provided in authority of URI
     * @var null|string
     */
    protected $password;

    /**
     * Check if the URI is a valid HTTP URI
     *
     * This applys additional HTTP specific validation rules beyond the ones
     * required by the generic URI syntax
     *
     * @return boolean
     * @see    Uri::isValid()
     */
    public function isValid()
    {
        return parent::isValid();
    }

    /**
     * Get the username part (before the ':') of the userInfo URI part
     *
     * @return null|string
     */
    public function getUser()
    {
        if (null !== $this->user) {
            return $this->user;
        }

        $this->parseUserInfo();
        return $this->user;
    }

    /**
     * Get the password part (after the ':') of the userInfo URI part
     *
     * @return string
     */
    public function getPassword()
    {
        if (null !== $this->password) {
            return $this->password;
        }

        $this->parseUserInfo();
        return $this->password;
    }

    /**
     * Set the username part (before the ':') of the userInfo URI part
     *
     * @param  string $user
     * @return Http
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Set the password part (after the ':') of the userInfo URI part
     *
     * @param  string $password
     * @return Http
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Validate the host part of an HTTP URI
     *
     * This overrides the common URI validation method with a DNS or IPv4 only
     * default. Users may still enforce allowing other host types.
     *
     * @param  string  $host
     * @param  integer $allowed
     * @return boolean
     */
    public static function validateHost($host, $allowed = self::HOST_DNSORIPV4)
    {
        return parent::validateHost($host, $allowed);
    }

    /**
     * Parse the user info into username and password segments
     *
     * Parses the user information into username and password segments, and
     * then sets the appropriate values.
     *
     * @return void
     */
    protected function parseUserInfo()
    {
        // No user information? we're done
        if (null === $this->userInfo) {
            return;
        }

        // If no ':' separator, we only have a username
        if (false === strpos($this->userInfo, ':')) {
            $this->setUser($this->userInfo);
            return;
        }

        // Split on the ':', and set both user and password
        list($user, $password) = explode(':', $this->userInfo, 2);
        $this->setUser($user);
        $this->setPassword($password);
    }

    /**
     * Return the URI port
     *
     * If no port is set, will return the default port according to the scheme
     *
     * @return integer
     * @see    Zend\Uri\Uri::getPort()
     */
    public function getPort()
    {
        if (empty($this->port)) {
            if (array_key_exists($this->scheme, self::$defaultPorts)) {
                return self::$defaultPorts[$this->scheme];
            }
        }
        return $this->port;
    }
}
