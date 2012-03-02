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
 * @package    Zend_Service_WindowsAzure
 * @subpackage Storage
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @uses       Zend_Service_WindowsAzure_Exception
 * @category   Zend
 * @package    Zend_Service_WindowsAzure
 * @subpackage Storage
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * 
 * @property string $Id           Id for the signed identifier
 * @property string $Start        The time at which the Shared Access Signature becomes valid.
 * @property string $Expiry       The time at which the Shared Access Signature becomes invalid.
 * @property string $Permissions  Signed permissions - read (r), write (w), delete (d) and list (l)
 */
class Zend_Service_WindowsAzure_Storage_SignedIdentifier
{
    /**
     * Data
     * 
     * @var array
     */
    protected $_data = null;
    
    /**
     * Constructor
     * 
     * @param string $id           Id for the signed identifier
     * @param string $start        The time at which the Shared Access Signature becomes valid.
     * @param string $expiry       The time at which the Shared Access Signature becomes invalid.
     * @param string $permissions  Signed permissions - read (r), write (w), delete (d) and list (l)
     */
    public function __construct($id = '', $start = '', $expiry = '', $permissions = '') 
    {
        $this->_data = array(
            'id'           => $id,
            'start'        => $start,
            'expiry'       => $expiry,
            'permissions'  => $permissions
        );
    }
    
    /**
     * Magic overload for setting properties
     * 
     * @param string $name     Name of the property
     * @param string $value    Value to set
     */
    public function __set($name, $value) {
        if (array_key_exists(strtolower($name), $this->_data)) {
            $this->_data[strtolower($name)] = $value;
            return;
        }

        throw new Zend_Service_WindowsAzure_Exception("Unknown property: " . $name);
    }

    /**
     * Magic overload for getting properties
     * 
     * @param string $name     Name of the property
     */
    public function __get($name) {
        if (array_key_exists(strtolower($name), $this->_data)) {
            return $this->_data[strtolower($name)];
        }

        throw new Zend_Service_WindowsAzure_Exception("Unknown property: " . $name);
    }
}
