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
 * @uses       Zend\XmlRpc\Value\Exception
 * @uses       Zend\XmlRpc\Value\Scalar
 * @category   Zend
 * @package    Zend_XmlRpc
 * @subpackage Value
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class DateTime extends Scalar
{
    /**
     * PHP compatible format string for XML/RPC datetime values
     *
     * @var string
     */
    protected $_phpFormatString = 'Ymd\\TH:i:s';

    /**
     * ISO compatible format string for XML/RPC datetime values
     *
     * @var string
     */
    protected $_isoFormatString = 'YYYYMMddTHH:mm:ss';

    /**
     * Set the value of a dateTime.iso8601 native type
     *
     * The value is in iso8601 format, minus any timezone information or dashes
     *
     * @param mixed $value Integer of the unix timestamp or any string that can be parsed
     *                     to a unix timestamp using the PHP strtotime() function
     */
    public function __construct($value)
    {
        $this->_type = self::XMLRPC_TYPE_DATETIME;

        if ($value instanceof \Zend\Date\Date) {
            $this->_value = $value->toString($this->_isoFormatString);
        } elseif ($value instanceof \DateTime) {
            $this->_value = $value->format($this->_phpFormatString);
        } elseif (is_numeric($value)) { // The value is numeric, we make sure it is an integer
            $this->_value = date($this->_phpFormatString, (int)$value);
        } else {
            $timestamp = strtotime($value);
            if ($timestamp === false || $timestamp == -1) { // cannot convert the value to a timestamp
                throw new Exception\ValueException('Cannot convert given value \''. $value .'\' to a timestamp');
            }

            $this->_value = date($this->_phpFormatString, $timestamp); // Convert the timestamp to iso8601 format
        }
    }

    /**
     * Return the value of this object as iso8601 dateTime value
     *
     * @return int As a Unix timestamp
     */
    public function getValue()
    {
        return $this->_value;
    }
}
