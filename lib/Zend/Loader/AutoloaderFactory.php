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
 */


namespace Zend\Loader;

require_once __DIR__ . '/SplAutoloader.php';

if (class_exists('Zend\Loader\AutoloaderFactory')) return;

/**
 * @category   Zend
 * @package    Zend_Loader
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class AutoloaderFactory
{
    const STANDARD_AUTOLOADER = 'Zend\Loader\StandardAutoloader';

    /**
     * @var array All autoloaders registered using the factory
     */
    protected static $loaders = array();

    /**
     * @var StandardAutoloader StandardAutoloader instance for resolving
     * autoloader classes via the include_path
     */
    protected static $standardAutoloader;

    /**
     * Factory for autoloaders
     *
     * Options should be an array or Traversable object of the following structure:
     * <code>
     * array(
     *     '<autoloader class name>' => $autoloaderOptions,
     * )
     * </code>
     *
     * The factory will then loop through and instantiate each autoloader with
     * the specified options, and register each with the spl_autoloader.
     *
     * You may retrieve the concrete autoloader instances later using
     * {@link getRegisteredAutoloaders()}.
     *
     * Note that the class names must be resolvable on the include_path or via
     * the Zend library, using PSR-0 rules (unless the class has already been
     * loaded).
     *
     * @param  array|Traversable $options (optional) options to use. Defaults to Zend\Loader\StandardAutoloader
     * @return void
     * @throws Exception\InvalidArgumentException for invalid options
     * @throws Exception\InvalidArgumentException for unloadable autoloader classes
     * @throws Exception\DomainException for autoloader classes not implementing SplAutoloader
     */
    public static function factory($options = null)
    {
        if (null === $options) {
            if (!isset(static::$loaders[static::STANDARD_AUTOLOADER])) {
                $autoloader = static::getStandardAutoloader();
                $autoloader->register();
                static::$loaders[static::STANDARD_AUTOLOADER] = $autoloader;
            }

            // Return so we don't hit the next check's exception (we're done here anyway)
            return;
        }

        if (!is_array($options) && !($options instanceof \Traversable)) {
            require_once __DIR__ . '/Exception/InvalidArgumentException.php';
            throw new Exception\InvalidArgumentException(
                             'Options provided must be an array or Traversable'
            );
        }

        foreach ($options as $class => $options) {
            if (!isset(static::$loaders[$class])) {
                $autoloader = static::getStandardAutoloader();
                if (!class_exists($class) && !$autoloader->autoload($class)) {
                    require_once 'Exception/InvalidArgumentException.php';
                    throw new Exception\InvalidArgumentException(
                                sprintf('Autoloader class "%s" not loaded', $class)
                    );
                }

                // unfortunately is_subclass_of is broken on some 5.3 versions
                // additionally instanceof is also broken for this use case
                if (version_compare(PHP_VERSION, '5.3.7', '>=')) {
                        if (!is_subclass_of($class, 'Zend\Loader\SplAutoloader')) {
                        require_once 'Exception/InvalidArgumentException.php';
                        throw new Exception\InvalidArgumentException(
                                    sprintf('Autoloader class %s must implement Zend\\Loader\\SplAutoloader', $class)
                        );
                    }
                }

                if ($class === static::STANDARD_AUTOLOADER) {
                    $autoloader->setOptions($options);
                } else {
                    $autoloader = new $class($options);
                }
                $autoloader->register();
                static::$loaders[$class] = $autoloader;
            } else {
                static::$loaders[$class]->setOptions($options);
            }
        }
    }

    /**
     * Get an list of all autoloaders registered with the factory
     *
     * Returns an array of autoloader instances.
     *
     * @return array
     */
    public static function getRegisteredAutoloaders()
    {
        return static::$loaders;
    }

    /**
     * Retrieves an autoloader by class name
     *
     * @param string $class
     * @return SplAutoloader
     * @throws Exception\InvalidArgumentException for non-registered class
     */
    public static function getRegisteredAutoloader($class)
    {
        if (!isset(static::$loaders[$class])) {
            require_once 'Exception/InvalidArgumentException.php';
            throw new Exception\InvalidArgumentException(sprintf('Autoloader class "%s" not loaded', $class));
        }
        return static::$loaders[$class];
    }

    /**
     * Unregisters all autoloaders that have been registered via the factory.
     * This will NOT unregister autoloaders registered outside of the fctory.
     *
     * @return void
     */
    public static function unregisterAutoloaders()
    {
        foreach (static::getRegisteredAutoloaders() as $class => $autoloader) {
            spl_autoload_unregister(array($autoloader, 'autoload'));
            unset(static::$loaders[$class]);
        }
    }

    /**
     * Unregister a single autoloader by class name
     *
     * @param  string $autoloaderClass
     * @return bool
     */
    public static function unregisterAutoloader($autoloaderClass)
    {
        if (!isset(static::$loaders[$autoloaderClass])) {
            return false;
        }

        $autoloader = static::$loaders[$autoloaderClass];
        spl_autoload_unregister(array($autoloader, 'autoload'));
        unset(static::$loaders[$autoloaderClass]);
        return true;
    }

    /**
     * Get an instance of the standard autoloader
     *
     * Used to attempt to resolve autoloader classes, using the
     * StandardAutoloader. The instance is marked as a fallback autoloader, to
     * allow resolving autoloaders not under the "Zend" namespace.
     *
     * @return SplAutoloader
     */
    protected static function getStandardAutoloader()
    {
        if (null !== static::$standardAutoloader) {
            return static::$standardAutoloader;
        }
        
        // Extract the filename from the classname
        $stdAutoloader = substr(strrchr(static::STANDARD_AUTOLOADER, '\\'), 1);

        if (!class_exists(static::STANDARD_AUTOLOADER)) {
            require_once __DIR__ . "/$stdAutoloader.php";
        }
        $loader = new StandardAutoloader();
        static::$standardAutoloader = $loader;
        return static::$standardAutoloader;
    }
}
