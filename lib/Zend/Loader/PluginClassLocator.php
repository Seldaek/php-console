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
 * @package    Zend_Loader
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * @namespace
 */
namespace Zend\Loader;

/**
 * Plugin class locator interface
 *
 * @category   Zend
 * @package    Zend_Loader
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
interface PluginClassLocator extends ShortNameLocator, \IteratorAggregate
{
    /**
     * Register a class to a given short name
     * 
     * @param  string $shortName 
     * @param  string $className 
     * @return PluginClassLocator
     */
    public function registerPlugin($shortName, $className);

    /**
     * Unregister a short name lookup
     * 
     * @param mixed $shortName 
     * @return void
     */
    public function unregisterPlugin($shortName);

    /**
     * Get a list of all registered plugins
     * 
     * @return array|Traversable
     */
    public function getRegisteredPlugins();
}

