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
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Filter\Encrypt;

/**
 * Encryption interface
 *
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
interface EncryptionAlgorithm
{
    /**
     * Encrypts $value with the defined settings
     *
     * @param  string $value Data to encrypt
     * @return string The encrypted data
     */
    public function encrypt($value);

    /**
     * Decrypts $value with the defined settings
     *
     * @param  string $value Data to decrypt
     * @return string The decrypted data
     */
    public function decrypt($value);

    /**
     * Return the adapter name
     * 
     * @return string
     */
    public function toString();
}
