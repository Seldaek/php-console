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
 * @package    Zend_Barcode
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Zend\Barcode;

use Zend\Loader\PluginBroker;

/**
 * Broker for Barcode Object instances
 *
 * @category   Zend
 * @package    Zend_Barcode
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class ObjectBroker extends PluginBroker
{
    /**
     * @var string Default plugin loading strategy
     */
    protected $defaultClassLoader = 'Zend\Barcode\ObjectLoader';

    /**
     * Determine if we have a valid Object
     *
     * @param  mixed $plugin
     * @return true
     * @throws Exception
     */
    protected function validatePlugin($plugin)
    {
        if (!$plugin instanceof Object\AbstractObject) {
            throw new Exception\InvalidArgumentException('Barcode Objects must extend Zend\Barcode\Object\AbstractObject');
        }
        return true;
    }
}
