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
 * @package    Zend_XmlRpc
 * @subpackage Value
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\XmlRpc\Value;
use Zend\XmlRpc\Exception;

/**
 * @uses       \Zend\XmlRpc\Value\Exception
 * @uses       \Zend\XmlRpc\Value\Scalar
 * @category   Zend
 * @package    Zend_XmlRpc
 * @subpackage Value
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Integer extends Scalar
{

    /**
     * Set the value of an integer native type
     *
     * @param int $value
     */
    public function __construct($value)
    {
        if ($value > PHP_INT_MAX) {
            throw new Exception\ValueException('Overlong integer given');
        }

        $this->_type = self::XMLRPC_TYPE_INTEGER;
        $this->_value = (int)$value;    // Make sure this value is integer
    }

    /**
     * Return the value of this object, convert the XML-RPC native integer value into a PHP integer
     *
     * @return int
     */
    public function getValue()
    {
        return $this->_value;
    }
}
