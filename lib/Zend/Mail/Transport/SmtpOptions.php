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
 * @package    Zend_Mail
 * @subpackage Transport
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Zend\Mail\Transport;

use Zend\Mail\Exception,
    Zend\Stdlib\Options;

/**
 * @category   Zend
 * @package    Zend_Mail
 * @subpackage Transport
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class SmtpOptions extends Options
{
    /**
     * @var string Local client hostname
     */
    protected $name = 'localhost';

    /**
     * @var string
     */
    protected $connectionClass = 'smtp';

    /**
     * Connection configuration (passed to the underlying Protocol class)
     * 
     * @var array
     */
    protected $connectionConfig = array();

    /**
     * @var string Remote SMTP hostname or IP
     */
    protected $host = '127.0.0.1';

    /**
     * @var int
     */
    protected $port = 25;

    /**
     * Return the local client hostname
     *
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }
 
    /**
     * Set the local client hostname or IP
     *
     * @todo   hostname/IP validation
     * @param  string $name
     * @return SmtpOptions
     */
    public function setName($name)
    {
        if (!is_string($name) && $name !== null) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Name must be a string or null; argument of type "%s" provided',
                (is_object($name) ? get_class($name) : gettype($name))
            ));
        }
        $this->name = $name;
        return $this;
    }

    /**
     * Get connection class
     *
     * This should be either the class Zend\Mail\Protocol\Smtp or a class 
     * extending it -- typically a class in the Zend\Mail\Protocol\Smtp\Auth
     * namespace.
     *
     * @return null|string
     */
    public function getConnectionClass() 
    {
        return $this->connectionClass;
    }

    /**
     * Set connection class 
     *
     * @param  string $connectionClass the value to be set
     * @return SmtpOptions
     */
    public function setConnectionClass($connectionClass) 
    {
        if (!is_string($connectionClass) && $connectionClass !== null) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Connection class must be a string or null; argument of type "%s" provided',
                (is_object($connectionClass) ? get_class($connectionClass) : gettype($connectionClass))
            ));
        }
        $this->connectionClass = $connectionClass;
        return $this;
    }

    /**
     * Get connection configuration array
     * 
     * @return array
     */
    public function getConnectionConfig()
    {
        return $this->connectionConfig;
    }

    /**
     * Set connection configuration array
     * 
     * @param  array $connectionConfig 
     * @return SmtpOptions
     */
    public function setConnectionConfig(array $connectionConfig)
    {
        $this->connectionConfig = $connectionConfig;
        return $this;
    }

    /**
     * Get the host name
     * 
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the SMTP host
     * 
     * @todo   hostname/IP validation
     * @param  string $host 
     * @return SmtpOptions
     */
    public function setHost($host)
    {
        $this->host = (string) $host;
    }

    /**
     * Get the port the SMTP server runs on
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }
 
    /**
     * Set the port the SMTP server runs on
     *
     * @param  int $port
     * @return SmtpOptions
     */
    public function setPort($port)
    {
        $port = (int) $port;
        if ($port < 1) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Port must be greater than 1; received "%d"',
                $port
            ));
        }
        $this->port = $port;
        return $this;
    }
}
