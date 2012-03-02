<?php

namespace Zend\Di\Definition;

class ClassDefinition implements Definition, PartialMarker
{

    protected $class = null;
    protected $supertypes = array();
    protected $instantiator = null;
    protected $methods = array();
    protected $methodParameters = array();


    public function __construct($class)
    {
        $this->class = $class;
    }

    public function setInstantiator($instantiator)
    {
        $this->instantiator = $instantiator;
    }

    public function setSupertypes(array $supertypes)
    {
        $this->supertypes = $supertypes;
    }

    public function addMethod($method, $isRequired = null)
    {
        if ($isRequired === null) {
            $isRequired = ($method === '__construct') ? true : false;
        }
        $this->methods[$method] = (bool) $isRequired;
    }

    /**
     * @param $method
     * @param $parameterName
     * @param array $parameterInfo (keys: required, type)
     */
    public function addMethodParameter($method, $parameterName, array $parameterInfo)
    {
        if (!array_key_exists($method, $this->methods)) {
            $this->methods[$method] = ($method === '__construct') ? true : false;
        }

        if (!array_key_exists($method, $this->methodParameters)) {
            $this->methodParameters[$method] = array();
        }

        $type = (isset($parameterInfo['type'])) ? $parameterInfo['type'] : null;
        $required = (isset($parameterInfo['required'])) ? (bool) $parameterInfo['required'] : false;

        $fqName = $this->class . '::' . $method . ':' . $parameterName;
        $this->methodParameters[$method][$fqName] = array(
            $parameterName, $type, $required
        );
    }

    /**
     * @return string[]
     */
    public function getClasses()
    {
        return array($this->class);
    }

    /**
     * @param string $class
     * @return bool
     */
    public function hasClass($class)
    {
        return ($class === $this->class);
    }

    /**
     * @param string $class
     * @return string[]
     */
    public function getClassSupertypes($class)
    {
        return $this->supertypes;
    }

    /**
     * @param string $class
     * @return string|array
     */
    public function getInstantiator($class)
    {
        return $this->instantiator;
    }

    /**
     * @param string $class
     * @return bool
     */
    public function hasMethods($class)
    {
        return ($this->methods);
    }

    /**
     * @param string $class
     * @return string[]
     */
    public function getMethods($class)
    {
        return $this->methods;
    }

    /**
     * @param string $class
     * @param string $method
     * @return bool
     */
    public function hasMethod($class, $method)
    {
        if (is_array($this->methods)) {
            return array_key_exists($method, $this->methods);
        } else {
            return null;
        }
    }

    /**
     * @param string $class
     * @param string $method
     * @return bool
     */
    public function hasMethodParameters($class, $method)
    {
        return (array_key_exists($method, $this->methodParameters));
    }

    /**
     * getMethodParameters() return information about a methods parameters.
     *
     * Should return an ordered named array of parameters for a given method.
     * Each value should be an array, of length 4 with the following information:
     *
     * array(
     *     0, // string|null: Type Name (if it exists)
     *     1, // bool: whether this param is required
     *     2, // string: fully qualified path to this parameter
     * );
     *
     *
     * @param $class
     * @param $method
     * @return array[]
     */
    public function getMethodParameters($class, $method)
    {
        if (array_key_exists($method, $this->methodParameters)) {
            return $this->methodParameters[$method];
        }
        return null;
    }
}