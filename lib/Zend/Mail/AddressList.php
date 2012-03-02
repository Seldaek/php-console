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
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Zend\Mail;

use Countable,
    Iterator;

/**
 * @category   Zend
 * @package    Zend_Mail
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class AddressList implements Countable, Iterator
{
    /**
     * List of Address objects we're managing
     * 
     * @var array
     */
    protected $addresses = array();

    /**
     * Add an address to the list
     * 
     * @param  string|AddressDescription $emailOrAddress 
     * @param  null|string $name 
     * @return AddressList
     */
    public function add($emailOrAddress, $name = null)
    {
        if (is_string($emailOrAddress)) {
            $emailOrAddress = $this->createAddress($emailOrAddress, $name);
        }
        if (!$emailOrAddress instanceof AddressDescription) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects an email address or %s\Address object as its first argument; received "%s"',
                __METHOD__,
                __NAMESPACE__,
                (is_object($emailOrAddress) ? get_class($emailOrAddress) : gettype($emailOrAddress))
            ));
        }

        $email = strtolower($emailOrAddress->getEmail());
        if ($this->has($email)) {
            return $this;
        }

        $this->addresses[$email] = $emailOrAddress;
        return $this;
    }

    /**
     * Add many addresses at once
     *
     * If an email key is provided, it will be used as the email, and the value 
     * as the name. Otherwise, the value is passed as the sole argument to add(), 
     * and, as such, can be either email strings or AddressDescription objects.
     * 
     * @param  array $addresses 
     * @return AddressList
     */
    public function addMany(array $addresses)
    {
        foreach ($addresses as $key => $value) {
            if (is_int($key) || is_numeric($key)) {
                $this->add($value);
            } elseif (is_string($key)) {
                $this->add($key, $value);
            } else {
                throw new Exception\RuntimeException(sprintf(
                    'Invalid key type in provided addresses array ("%s")',
                    (is_object($key) ? get_class($key) : var_export($key, 1))
                ));
            }
        }
        return $this;
    }

    /**
     * Merge another address list into this one 
     * 
     * @param  AddressList $addressList 
     * @return AddressList
     */
    public function merge(AddressList $addressList)
    {
        foreach ($addressList as $address) {
            $this->add($address);
        }
        return $this;
    }

    /**
     * Does the email exist in this list?
     * 
     * @param  string $email 
     * @return bool
     */
    public function has($email)
    {
        $email = strtolower($email);
        return isset($this->addresses[$email]);
    }

    /**
     * Get an address by email
     * 
     * @param  string $email 
     * @return false|AddressDescription
     */
    public function get($email)
    {
        $email = strtolower($email);
        if (!isset($this->addresses[$email])) {
            return false;
        }

        return $this->addresses[$email];
    }

    /**
     * Delete an address from the list
     * 
     * @param  string $email
     * @return bool
     */
    public function delete($email)
    {
        $email = strtolower($email);
        if (!isset($this->addresses[$email])) {
            return false;
        }

        unset($this->addresses[$email]);
        return true;
    }

    /**
     * Return count of addresses
     * 
     * @return int
     */
    public function count()
    {
        return count($this->addresses);
    }

    /**
     * Rewind iterator
     * 
     * @return void
     */
    public function rewind()
    {
        return reset($this->addresses);
    }

    /**
     * Return current item in iteration
     * 
     * @return Address
     */
    public function current()
    {
        return current($this->addresses);
    }

    /**
     * Return key of current item of iteration
     * 
     * @return string
     */
    public function key()
    {
        return key($this->addresses);
    }

    /**
     * Move to next item
     * 
     * @return void
     */
    public function next()
    {
        return next($this->addresses);
    }

    /**
     * Is the current item of iteration valid?
     * 
     * @return bool
     */
    public function valid()
    {
        $key = key($this->addresses);
        return ($key !== null && $key !== false);
    }

    /**
     * Create an address object 
     * 
     * @param  string $email 
     * @param  string|null $name 
     * @return Address
     */
    protected function createAddress($email, $name)
    {
        return new Address($email, $name);
    }
}
