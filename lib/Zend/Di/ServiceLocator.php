<?php

namespace Zend\Di;

class ServiceLocator implements ServiceLocation
{
    /**
     * Map of service names to methods
     *
     * As an example, you might define a getter method "getFoo", and map it to 
     * the service name "foo":
     *
     * <code>
     * protected $map = array('foo' => 'getFoo');
     * </code>
     *
     * When encountered, the return value of that method will be used.
     *
     * Methods mapped in this way may expect a single, array argument, the 
     * $params passed to {@link get()}, if any.
     * 
     * @var array
     */
    protected $map = array();

    /**
     * Registered services and cached values
     * 
     * @var array
     */
    protected $services = array();

    /**
     * Register a service with the locator
     * 
     * @param  string $name 
     * @param  mixed $service 
     * @return ServiceLocator
     */
    public function set($name, $service)
    {
        $this->services[$name] = $service;
        return $this;
    }

    /**
     * Retrieve a registered service
     *
     * Tests first if a value is registered for the service, and, if so, 
     * returns it.
     *
     * If the value returned is a non-object callback or closure, the return
     * value is retrieved, stored, and returned. Parameters passed to the method 
     * are passed to the callback, but only on the first retrieval.
     *
     * If the service requested matches a method in the method map, the return
     * value of that method is returned. Parameters are passed to the matching
     * method.
     * 
     * @param  string $name 
     * @param  array $params 
     * @return mixed
     */
    public function get($name, array $params = array())
    {
        if (!isset($this->services[$name])) {
            if (!isset($this->map[$name])) {
                return null;
            }
            $method = $this->map[$name];
            return $this->$method($params);
        }

        $service = $this->services[$name];
        if ($service instanceof \Closure
            || (!is_object($service) && is_callable($service))
        ) {
            $this->services[$name] = $service = call_user_func_array($service, $params);
        }

        return $service;
    }
}
