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

use ArrayIterator,
    IteratorAggregate,
    Traversable;

/**
 * Plugin class locator interface
 *
 * @category   Zend
 * @package    Zend_Loader
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class PluginClassLoader implements PluginClassLocator
{
    /**
     * List of plugin name => class name pairs
     * @var array
     */
    protected $plugins = array();

    /**
     * Static map allow global seeding of plugin loader
     * @var array
     */
    protected static $staticMap = array();

    /**
     * Constructor
     * 
     * @param  null|array|Traversable $map If provided, seeds the loader with a map
     * @return void
     */
    public function __construct($map = null)
    {
        // Merge in static overrides
        if (!empty(static::$staticMap)) {
            $this->registerPlugins(static::$staticMap);
        }

        // Merge in constructor arguments
        if ($map !== null) {
            $this->registerPlugins($map);
        }
    }

    /**
     * Add a static map of plugins
     *
     * A null value will clear the static map.
     * 
     * @param  null|array|Traversable $map 
     * @return void
     */
    public static function addStaticMap($map)
    {
        if (null === $map) {
            static::$staticMap = array();
            return;
        }

        if (!is_array($map) && !$map instanceof \Traversable) {
            throw new Exception\InvalidArgumentException('Expects an array or Traversable object');
        }
        foreach ($map as $key => $value) {
            static::$staticMap[$key] = $value;
        }
    }

    /**
     * Register a class to a given short name
     * 
     * @param  string $shortName 
     * @param  string $className 
     * @return PluginClassLoader
     */
    public function registerPlugin($shortName, $className)
    {
        $this->plugins[strtolower($shortName)] = $className;
        return $this;
    }

    /**
     * Register many plugins at once
     *
     * If $map is a string, assumes that the map is the class name of a 
     * Traversable object (likely a ShortNameLocator); it will then instantiate
     * this class and use it to register plugins.
     *
     * If $map is an array or Traversable object, it will iterate it to 
     * register plugin names/classes.
     *
     * For all other arguments, or if the string $map is not a class or not a 
     * Traversable class, an exception will be raised.
     * 
     * @param  string|array|Traversable $map 
     * @return PluginClassLoader
     * @throws Exception\InvalidArgumentException
     */
    public function registerPlugins($map)
    {
        if (is_string($map)) {
            if (!class_exists($map)) {
                throw new Exception\InvalidArgumentException('Map class provided is invalid');
            }
            $map = new $map;
        }
        if (is_array($map)) {
            $map = new ArrayIterator($map);
        }
        if (!$map instanceof Traversable) {
            throw new Exception\InvalidArgumentException('Map provided is invalid; must be traversable');
        }

        // iterator_apply doesn't work as expected with IteratorAggregate
        if ($map instanceof IteratorAggregate) {
            $map = $map->getIterator();
        }

        foreach ($map as $name => $class) {
            $this->registerPlugin($name, $class);
        }

        return $this;
    }

    /**
     * Unregister a short name lookup
     * 
     * @param mixed $shortName 
     * @return PluginClassLoader
     */
    public function unregisterPlugin($shortName)
    {
        $lookup = strtolower($shortName);
        if (array_key_exists($lookup, $this->plugins)) {
            unset($this->plugins[$lookup]);
        }
        return $this;
    }

    /**
     * Get a list of all registered plugins
     * 
     * @return array|Traversable
     */
    public function getRegisteredPlugins()
    {
        return $this->plugins;
    }

    /**
     * Whether or not a plugin by a specific name has been registered
     *
     * @param  string $name
     * @return bool
     */
    public function isLoaded($name)
    {
        $lookup = strtolower($name);
        return isset($this->plugins[$lookup]);
    }

    /**
     * Return full class name for a named helper
     *
     * @param  string $name
     * @return string|false
     */
    public function getClassName($name)
    {
        return $this->load($name);
    }

    /**
     * Load a helper via the name provided
     *
     * @param  string $name
     * @return string|false
     */
    public function load($name)
    {
        if (!$this->isLoaded($name)) {
            return false;
        }
        return $this->plugins[strtolower($name)];
    }

    /**
     * Defined by IteratorAggregate
     *
     * Returns an instance of ArrayIterator, containing a map of 
     * all plugins
     * 
     * @return Iterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->plugins);
    }
}
