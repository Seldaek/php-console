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
 * @package    Zend_Cache
 * @subpackage Storage
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Zend\Cache\Storage;

use Zend\Cache\Exception,
    Zend\Loader\PluginBroker as BasePluginBroker;

/**
 * Broker for cache storage plugin instances
 *
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage Storage
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class PluginBroker extends BasePluginBroker
{
    /**
     * Default class loader
     *
     * @var string Default plugin loading strategy
     */
    protected $defaultClassLoader = 'Zend\Cache\Storage\PluginLoader';

    /**
     * Determine if we have a valid adapter
     * 
     * @param  mixed $plugin 
     * @return true
     * @throws Exception
     */
    protected function validatePlugin($plugin)
    {
        if (!$plugin instanceof Plugin) {
            throw new Exception\RuntimeException(
                'Cache storage plugins must implement Zend\Cache\Storage\Plugin'
            );
        }
        return true;
    }
}
