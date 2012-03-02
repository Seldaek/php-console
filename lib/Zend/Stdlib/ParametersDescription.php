<?php

namespace Zend\Stdlib;

use ArrayAccess,
    Countable,
    Serializable,
    Traversable;

/* 
 * Basically, an ArrayObject. You could simply define something like:
 *     class QueryParams extends ArrayObject implements Parameters {}
 * and have 90% of the functionality
 */
interface ParametersDescription extends ArrayAccess, Countable, Serializable, Traversable
{
    public function __construct(array $values = null);

    /* Allow deserialization from standard array */
    public function fromArray(array $values);

    /* Allow deserialization from raw body; e.g., for PUT requests */
    public function fromString($string);

    /* Allow serialization back to standard array */
    public function toArray();

    /* Allow serialization to query format; e.g., for PUT or POST requests */
    public function toString();
    
    public function get($name, $default = null);
    
    public function set($name, $value);
}
