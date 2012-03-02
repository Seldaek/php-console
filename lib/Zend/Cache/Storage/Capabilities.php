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

use stdClass,
    Zend\Cache\Exception,
    Zend\EventManager\EventManager;

/**
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage Storage
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Capabilities
{
    /**
     * The event manager
     *
     * @var null|EventManager
     */
    protected $eventManager;

    /**
     * A marker to set/change capabilities
     *
     * @var stdClass
     */
    protected $marker;

    /**
     * Base capabilities
     *
     * @var null|Capabilities
     */
    protected $baseCapabilities;

    /**
     * Clear all namespaces
     */
    protected $_clearAllNamespaces;

    /**
     * Clear by namespace
     */
    protected $_clearByNamespace;

    /**
     * Expire read
     */
    protected $_expiredRead;

    /**
     * Iterable
     */
    protected $_iterable;

    /**
     * Max key length
     */
    protected $_maxKeyLength;

    /**
     * Max ttl
     */
    protected $_maxTtl;

    /**
     * Namespace is prefix
     */
    protected $_namespaceIsPrefix;

    /**
     * Namespace separator
     */
    protected $_namespaceSeparator;

    /**
     * Static ttl
     */
    protected $_staticTtl;

   /**
    * Capability property
    *
    * If it's NULL the capability isn't set and the getter
    * returns the base capability or the default value.
    *
    * @var null|mixed
    */
    protected $_supportedDatatypes;

    /**
     * Supported metdata
     */
    protected $_supportedMetadata;

    /**
     * Supports tagging? 
     * 
     * @var bool
     */
    protected $_tagging;

    /**
     * Ttl precision
     */
    protected $_ttlPrecision;

    /**
     * Use request time
     */
    protected $_useRequestTime;

    /**
     * Constructor
     *
     * @param stdClass $marker
     * @param array $capabilities
     * @param null|Zend\Cache\Storage\Capabilities $baseCapabilities
     */
    public function __construct(
        stdClass $marker,
        array $capabilities = array(),
        Capabilities $baseCapabilities = null
    ) {
        $this->marker = $marker;
        $this->baseCapabilities = $baseCapabilities;
        foreach ($capabilities as $name => $value) {
            $this->setCapability($marker, $name, $value);
        }
    }

    /**
     * Returns if the dependency of Zend\EventManager is available
     *
     * @return boolean
     */
    public function hasEventManager()
    {
        return ($this->eventManager !== null || class_exists('Zend\EventManager\EventManager'));
    }

    /**
     * Get the event manager
     *
     * @return EventManager
     * @throws Exception\MissingDependencyException
     */
    public function getEventManager()
    {
        if ($this->eventManager instanceof EventManager) {
            return $this->eventManager;
        }

        if (!class_exists('Zend\EventManager\EventManager')) {
            throw new Exception\MissingDependencyException('Zend\EventManager\EventManager not found');
        }

        // create a new event manager object
        $eventManager = new EventManager();

        // trigger change event on change of a base capability
        if ($this->baseCapabilities && $this->baseCapabilities->hasEventManager()) {
            $onChange = function ($event) use ($eventManager)  {
                $eventManager->trigger('change', $event->getTarget(), $event->getParams());
            };
            $this->baseCapabilities->getEventManager()->attach('change', $onChange);
        }

        // register event manager
        $this->eventManager = $eventManager;

        return $this->eventManager;
    }

    /**
     * Get supported datatypes
     *
     * @return array
     */
    public function getSupportedDatatypes()
    {
        return $this->getCapability('supportedDatatypes', array(
            'NULL'     => false,
            'boolean'  => false,
            'integer'  => false,
            'double'   => false,
            'string'   => true,
            'array'    => false,
            'object'   => false,
            'resource' => false,
        ));
    }

    /**
     * Set supported datatypes
     *
     * @param  stdClass $marker
     * @param  array $datatypes
     * @return Capabilities Fluent interface
     */
    public function setSupportedDatatypes(stdClass $marker, array $datatypes)
    {
        $allTypes = array(
            'array',
            'boolean',
            'double',
            'integer',
            'NULL',
            'object',
            'resource',
            'string',
        );

        // check/normalize datatype values
        foreach ($datatypes as $type => &$toType) {
            if (!in_array($type, $allTypes)) {
                throw new Exception\InvalidArgumentException("Unknown datatype '{$type}'");
            }

            if (is_string($toType)) {
                $toType = strtolower($toType);
                if (!in_array($toType, $allTypes)) {
                    throw new Exception\InvalidArgumentException("Unknown datatype '{$toType}'");
                }
            } else {
                $toType = (bool) $toType;
            }
        }

        // add missing datatypes as not supported
        $missingTypes = array_diff($allTypes, array_keys($datatypes));
        foreach ($missingTypes as $type) {
            $datatypes[type] = false;
        }

        return $this->setCapability($marker, 'supportedDatatypes', $datatypes);
    }

    /**
     * Get supported metadata
     *
     * @return array
     */
    public function getSupportedMetadata()
    {
        return $this->getCapability('supportedMetadata', array());
    }

    /**
     * Set supported metadata
     *
     * @param  stdClass $marker
     * @param  string[] $metadata
     * @return Capabilities Fluent interface
     */
    public function setSupportedMetadata(stdClass $marker, array $metadata)
    {
        foreach ($metadata as $name) {
            if (!is_string($name)) {
                throw new Exception\InvalidArgumentException('$metadata must be an array of strings');
            }
        }
        return $this->setCapability($marker, 'supportedMetadata', $metadata);
    }

    /**
     * Get maximum supported time-to-live
     *
     * @return int 0 means infinite
     */
    public function getMaxTtl()
    {
        return $this->getCapability('maxTtl', 0);
    }

    /**
     * Set maximum supported time-to-live
     *
     * @param  stdClass $marker
     * @param  int $maxTtl
     * @return Capabilities Fluent interface
     */
    public function setMaxTtl(stdClass $marker, $maxTtl)
    {
        $maxTtl = (int)$maxTtl;
        if ($maxTtl < 0) {
            throw new Exception\InvalidArgumentException('$maxTtl must be greater or equal 0');
        }
        return $this->setCapability($marker, 'maxTtl', $maxTtl);
    }

    /**
     * Is the time-to-live handled static (on write)
     * or dynamic (on read)
     *
     * @return boolean
     */
    public function getStaticTtl()
    {
        return $this->getCapability('staticTtl', false);
    }

    /**
     * Set if the time-to-live handled static (on write) or dynamic (on read)
     *
     * @param  stdClass $marker
     * @param  boolean $flag
     * @return Capabilities Fluent interface
     */
    public function setStaticTtl(stdClass $marker, $flag)
    {
        return $this->setCapability($marker, 'staticTtl', (bool)$flag);
    }

    /**
     * Get time-to-live precision
     *
     * @return float
     */
    public function getTtlPrecision()
    {
        return $this->getCapability('ttlPrecision', 1);
    }

    /**
     * Set time-to-live precision
     *
     * @param  stdClass $marker
     * @param  float $ttlPrecision
     * @return Capabilities Fluent interface
     */
    public function setTtlPrecision(stdClass $marker, $ttlPrecision)
    {
        $ttlPrecision = (float) $ttlPrecision;
        if ($ttlPrecision <= 0) {
            throw new Exception\InvalidArgumentException('$ttlPrecision must be greater than 0');
        }
        return $this->setCapability($marker, 'ttlPrecision', $ttlPrecision);
    }

    /**
     * Get use request time
     *
     * @return boolean
     */
    public function getUseRequestTime()
    {
        return $this->getCapability('useRequestTime', false);
    }

    /**
     * Set use request time
     *
     * @param  stdClass $marker
     * @param  boolean $flag
     * @return Capabilities Fluent interface
     */
    public function setUseRequestTime(stdClass $marker, $flag)
    {
        return $this->setCapability($marker, 'useRequestTime', (bool)$flag);
    }

    /**
     * Get if expired items are readable
     *
     * @return boolean
     */
    public function getExpiredRead()
    {
        return $this->getCapability('expiredRead', false);
    }

    /**
     * Set if expired items are readable
     *
     * @param  stdClass $marker
     * @param  boolean $flag
     * @return Capabilities Fluent interface
     */
    public function setExpiredRead(stdClass $marker, $flag)
    {
        return $this->setCapability($marker, 'expiredRead', (bool)$flag);
    }

    /**
     * Get maximum key lenth
     *
     * @return int -1 means unknown, 0 means infinite
     */
    public function getMaxKeyLength()
    {
        return $this->getCapability('maxKeyLength', -1);
    }

    /**
     * Set maximum key lenth
     *
     * @param  stdClass $marker
     * @param  int $maxKeyLength
     * @return Capabilities Fluent interface
     */
    public function setMaxKeyLength(stdClass $marker, $maxKeyLength)
    {
        $maxKeyLength = (int) $maxKeyLength;
        if ($maxKeyLength < -1) {
            throw new Exception\InvalidArgumentException('$maxKeyLength must be greater or equal than -1');
        }
        return $this->setCapability($marker, 'maxKeyLength', $maxKeyLength);
    }

    /**
     * Get if namespace support is implemented as prefix
     *
     * @return boolean
     */
    public function getNamespaceIsPrefix()
    {
        return $this->getCapability('namespaceIsPrefix', true);
    }

    /**
     * Set if namespace support is implemented as prefix
     *
     * @param  stdClass $marker
     * @param  boolean $flag
     * @return Capabilities Fluent interface
     */
    public function setNamespaceIsPrefix(stdClass $marker, $flag)
    {
        return $this->setCapability($marker, 'namespaceIsPrefix', (bool)$flag);
    }

    /**
     * Get namespace separator if namespace is implemented as prefix
     *
     * @return string
     */
    public function getNamespaceSeparator()
    {
        return $this->getCapability('namespaceSeparator', '');
    }

    /**
     * Set the namespace separator if namespace is implemented as prefix
     *
     * @param  stdClass $marker
     * @param  string $separator
     * @return Capabilities Fluent interface
     */
    public function setNamespaceSeparator(stdClass $marker, $separator)
    {
        return $this->setCapability($marker, 'namespaceSeparator', (string) $separator);
    }

    /**
     * Get if items are iterable
     *
     * @return boolean
     */
    public function getIterable()
    {
        return $this->getCapability('iterable', false);
    }

    /**
     * Set if items are iterable
     *
     * @param  stdClass $marker
     * @param  boolean $flag
     * @return Capabilities Fluent interface
     */
    public function setIterable(stdClass $marker, $flag)
    {
        return $this->setCapability($marker, 'iterable', (bool)$flag);
    }

    /**
     * Get support to clear items of all namespaces
     *
     * @return boolean
     */
    public function getClearAllNamespaces()
    {
        return $this->getCapability('clearAllNamespaces', false);
    }

    /**
     * Set support to clear items of all namespaces
     *
     * @param  stdClass $marker
     * @param  boolean $flag
     * @return Capabilities Fluent interface
     */
    public function setClearAllNamespaces(stdClass $marker, $flag)
    {
        return $this->setCapability($marker, 'clearAllNamespaces', (bool)$flag);
    }

    /**
     * Get support to clear items by namespace
     *
     * @return boolean
     */
    public function getClearByNamespace()
    {
        return $this->getCapability('clearByNamespace', false);
    }

    /**
     * Set support to clear items by namespace
     *
     * @param  stdClass $marker
     * @param  boolean $flag
     * @return Capabilities Fluent interface
     */
    public function setClearByNamespace(stdClass $marker, $flag)
    {
        return $this->setCapability($marker, 'clearByNamespace', (bool)$flag);
    }

    /**
     * Set value for tagging
     *
     * @param  mixed tagging
     * @return $this
     */
    public function setTagging(stdClass $marker, $tagging)
    {
        return $this->setCapability($marker, 'tagging', (bool) $tagging);
    }
    
    /**
     * Get value for tagging
     *
     * @return mixed
     */
    public function getTagging()
    {
        return $this->getCapability('tagging', false);
    }

    /**
     * Get a capability
     *
     * @param  string $name
     * @param  mixed $default
     * @return mixed
     */
    protected function getCapability($name, $default = null)
    {
        $property = '_' . $name;
        if ($this->$property !== null) {
            return $this->$property;
        } elseif ($this->baseCapabilities) {
            $getMethod = 'get' . $name;
            return $this->baseCapabilities->$getMethod();
        }
        return $default;
    }

    /**
     * Change a capability
     *
     * @param  stdClass $marker
     * @param  string $name
     * @param  mixed $value
     * @return Capabilities Fluent interface
     * @throws Exception\InvalidArgumentException
     */
    protected function setCapability(stdClass $marker, $name, $value)
    {
        if ($this->marker !== $marker) {
            throw new Exception\InvalidArgumentException('Invalid marker');
        }

        $property = '_' . $name;
        if ($this->$property !== $value) {
            $this->$property = $value;
            $this->getEventManager()->trigger('change', $this, array(
                $name => $value
            ));
        }

        return $this;
    }
}
