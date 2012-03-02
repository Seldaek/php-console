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
 * @package    Zend_Soap
 * @subpackage AutoDiscover
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Soap;

use Zend\Uri,
    Zend\Soap\Wsdl,
    Zend\Soap\Wsdl\ComplexTypeStrategy,
    Zend\Server\Reflection\AbstractFunction,
    Zend\Server\Reflection,
    Zend\Server\Reflection\Prototype,
    Zend\Server\Reflection\ReflectionParameter,
    Zend\Soap\AutoDiscover\DiscoveryStrategy\ReflectionDiscovery;

/**
 * \Zend\Soap\AutoDiscover
 *
 * @category   Zend
 * @package    Zend_Soap
 * @subpackage AutoDiscover
 */
class AutoDiscover
{
    /**
     * @var string
     */
    protected $_serviceName;

    /**
     * @var \Zend\Server\Reflection
     */
    protected $_reflection = null;

    /**
     * Service function names
     *
     * @var array
     */
    protected $_functions = array();

    /**
     * Service class name
     *
     * @var string
     */
    protected $_class;

    /**
     * @var boolean
     */
    protected $_strategy;

    /**
     * Url where the WSDL file will be available at.
     *
     * @var WSDL Uri
     */
    protected $_uri;

    /**
     * soap:body operation style options
     *
     * @var array
     */
    protected $_operationBodyStyle = array('use' => 'encoded', 'encodingStyle' => "http://schemas.xmlsoap.org/soap/encoding/");

    /**
     * soap:operation style
     *
     * @var array
     */
    protected $_bindingStyle = array('style' => 'rpc', 'transport' => 'http://schemas.xmlsoap.org/soap/http');

    /**
     * Name of the class to handle the WSDL creation.
     *
     * @var string
     */
    protected $_wsdlClass = 'Zend\Soap\Wsdl';

    /**
     * Class Map of PHP to WSDL types.
     *
     * @var array
     */
    protected $_classMap = array();

    /**
     * Discovery strategy for types and other method details.
     *
     * @var Zend\Soap\AutoDiscover\DiscoveryStrategy
     */
    protected $_discoveryStrategy;

    /**
     * Constructor
     *
     * @param \Zend\Soap\Wsdl\ComplexTypeStrategy $strategy
     * @param string|Uri\Uri $endpointUri
     * @param string $wsdlClass
     */
    public function __construct(ComplexTypeStrategy $strategy = null, $endpointUri=null, $wsdlClass=null, array $classMap = array())
    {
        $this->_reflection = new Reflection();
        $this->_discoveryStrategy = new ReflectionDiscovery();

        if ($strategy !== null) {
            $this->setComplexTypeStrategy($strategy);
        }

        if($endpointUri !== null) {
            $this->setUri($endpointUri);
        }

        if($wsdlClass !== null) {
            $this->setWsdlClass($wsdlClass);
        }
    }

    /**
     * Set the discovery strategy for method type and other information.
     *
     * @param  AutoDiscover\DiscoveryStrategy $discoveryStrategy
     * @return Zend\Soap\AutoDiscover
     */
    public function setDiscoveryStrategy(DiscoveryStrategy $discoveryStrategy)
    {
        $this->_discoveryStrategy = $discoveryStrategy;
        return $this;
    }

    /**
     * @return AutoDiscover\DiscoveryStrategy
     */
    public function getDiscoveryStrategy()
    {
        return $this->_discoveryStrategy;
    }

    /**
     * Get the class map of php to wsdl qname types.
     *
     * @return array
     */
    public function getClassMap()
    {
        return $this->_classMap;
    }

    /**
     * Set the class map of php to wsdl qname types.
     */
    public function setClassMap($classMap)
    {
        $this->_classMap = $classMap;
        return $this;
    }

    /**
     * Set service name
     *
     * @param string $serviceName
     * @return AutoDiscover
     */
    public function setServiceName($serviceName)
    {
        $this->_serviceName = $serviceName;
        return $this;
    }

    /**
     * Get service name
     *
     * @return string
     */
    public function getServiceName()
    {
        if (!$this->_serviceName) {
            if ($this->_class) {
                return $this->_reflection->reflectClass($this->_class)
                                         ->getShortName();
            } else {
                throw new Exception\RuntimeException(
                    "No service name given. Call Autodiscover#setServiceName()."
                );
            }
        }

        return $this->_serviceName;
    }


    /**
     * Set the location at which the WSDL file will be availabe.
     *
     * @param  Uri\Uri|string $uri
     * @return \Zend\Soap\AutoDiscover
     * @throws \Zend\Soap\Exception\InvalidArgumentException
     */
    public function setUri($uri)
    {
        if (!is_string($uri) && !($uri instanceof Uri\Uri)) {
            throw new Exception\InvalidArgumentException(
                'No uri given to \Zend\Soap\AutoDiscover::setUri as string or \Zend\Uri\Uri instance.'
            );
        }
        $this->_uri = $uri;

        return $this;
    }

    /**
     * Return the current Uri that the SOAP WSDL Service will be located at.
     *
     * @return Uri\Uri
     */
    public function getUri()
    {
        if($this->_uri === null) {
            throw new Exception\RuntimeException("Missing uri. You have to explicitly configure the Endpoint Uri by calling AutoDiscover#setUri().");
        }
        if (is_string($this->_uri)) {
            $this->_uri = Uri\UriFactory::factory($this->_uri);
        }

        return $this->_uri;
    }

    /**
     * Set the name of the WSDL handling class.
     *
     * @param  string $wsdlClass
     * @return \Zend\Soap\AutoDiscover
     * @throws \Zend\Soap\Exception\InvalidArgumentException
     */
    public function setWsdlClass($wsdlClass)
    {
        if (!is_string($wsdlClass) && !is_subclass_of($wsdlClass, 'Zend\Soap\Wsdl')) {
            throw new Exception\InvalidArgumentException(
                'No \Zend\Soap\Wsdl subclass given to Zend\Soap\AutoDiscover::setWsdlClass as string.'
            );
        }
        $this->_wsdlClass = $wsdlClass;

        return $this;
    }

    /**
     * Return the name of the WSDL handling class.
     *
     * @return string
     */
    public function getWsdlClass()
    {
        return $this->_wsdlClass;
    }

    /**
     * Set options for all the binding operations soap:body elements.
     *
     * By default the options are set to 'use' => 'encoded' and
     * 'encodingStyle' => "http://schemas.xmlsoap.org/soap/encoding/".
     *
     * @param  array $operationStyle
     * @return \Zend\Soap\AutoDiscover
     * @throws \Zend\Soap\Exception\InvalidArgumentException
     */
    public function setOperationBodyStyle(array $operationStyle=array())
    {
        if(!isset($operationStyle['use'])) {
            throw new Exception\InvalidArgumentException("Key 'use' is required in Operation soap:body style.");
        }
        $this->_operationBodyStyle = $operationStyle;
        return $this;
    }

    /**
     * Set Binding soap:binding style.
     *
     * By default 'style' is 'rpc' and 'transport' is 'http://schemas.xmlsoap.org/soap/http'.
     *
     * @param  array $bindingStyle
     * @return \Zend\Soap\AutoDiscover
     */
    public function setBindingStyle(array $bindingStyle=array())
    {
        if(isset($bindingStyle['style'])) {
            $this->_bindingStyle['style'] = $bindingStyle['style'];
        }
        if(isset($bindingStyle['transport'])) {
            $this->_bindingStyle['transport'] = $bindingStyle['transport'];
        }
        return $this;
    }

    /**
     * Set the strategy that handles functions and classes that are added AFTER this call.
     *
     * @param  \Zend\Soap\Wsdl\ComplexTypeStrategy $strategy
     * @return \Zend\Soap\AutoDiscover
     */
    public function setComplexTypeStrategy(ComplexTypeStrategy $strategy)
    {
        $this->_strategy = $strategy;

        return $this;
    }

    /**
     * Set the Class the SOAP server will use
     *
     * @param string $class Class Name
     * @return \Zend\Soap\AutoDiscover
     */
    public function setClass($class)
    {
        $this->_class = $class;
        return $this;
    }

    /**
     * Add a Single or Multiple Functions to the WSDL
     *
     * @param string $function Function Name
     * @return \Zend\Soap\AutoDiscover
     */
    public function addFunction($function)
    {
        $this->_functions[] = $function;
        return $this;
    }

    /**
     * Generate the WSDL for a service class.
     *
     * @return Zend\Soap\Wsdl
     */
    protected function _generateClass()
    {
        return $this->_generateWsdl($this->_reflection->reflectClass($this->_class)->getMethods());
    }

    /**
     * Generate the WSDL for a set of functions.
     *
     * @return Zend\Soap\Wsdl
     */
    protected function _generateFunctions()
    {
        $methods = array();
        foreach (array_unique($this->_functions) as $func) {
            $methods[] = $this->_reflection->reflectFunction($func);
        }

        return $this->_generateWsdl($methods);
    }

    /**
     * Generate the WSDL for a set of reflection method instances.
     *
     * @return Zend\Soap\Wsdl
     */
    protected function _generateWsdl(array $reflectionMethods)
    {
        $uri = $this->getUri();

        $serviceName = $this->getServiceName();
        $wsdl = new $this->_wsdlClass($serviceName, $uri, $this->_strategy, $this->_classMap);

        // The wsdl:types element must precede all other elements (WS-I Basic Profile 1.1 R2023)
        $wsdl->addSchemaTypeSection();

        $port = $wsdl->addPortType($serviceName . 'Port');
        $binding = $wsdl->addBinding($serviceName . 'Binding', 'tns:' .$serviceName. 'Port');

        $wsdl->addSoapBinding($binding, $this->_bindingStyle['style'], $this->_bindingStyle['transport']);
        $wsdl->addService($serviceName . 'Service', $serviceName . 'Port', 'tns:' . $serviceName . 'Binding', $uri);

        foreach ($reflectionMethods as $method) {
            $this->_addFunctionToWsdl($method, $wsdl, $port, $binding);
        }

        return $wsdl;
    }

    /**
     * Add a function to the WSDL document.
     *
     * @param $function \Zend\Server\Reflection\AbstractFunction function to add
     * @param $wsdl \Zend\Soap\Wsdl WSDL document
     * @param $port object wsdl:portType
     * @param $binding object wsdl:binding
     * @return void
     */
    protected function _addFunctionToWsdl($function, $wsdl, $port, $binding)
    {
        $uri = $this->getUri();

        // We only support one prototype: the one with the maximum number of arguments
        $prototype = null;
        $maxNumArgumentsOfPrototype = -1;
        foreach ($function->getPrototypes() as $tmpPrototype) {
            $numParams = count($tmpPrototype->getParameters());
            if ($numParams > $maxNumArgumentsOfPrototype) {
                $maxNumArgumentsOfPrototype = $numParams;
                $prototype = $tmpPrototype;
            }
        }
        if ($prototype === null) {
            throw new Exception\InvalidArgumentException("No prototypes could be found for the '" . $function->getName() . "' function");
        }

        $functionName = $wsdl->translateType($function->getName());

        // Add the input message (parameters)
        $args = array();
        if ($this->_bindingStyle['style'] == 'document') {
            // Document style: wrap all parameters in a sequence element
            $sequence = array();
            foreach ($prototype->getParameters() as $param) {
                $sequenceElement = array(
                    'name' => $param->getName(),
                    'type' => $wsdl->getType($this->_discoveryStrategy->getFunctionParameterType($param))
                );
                if ($param->isOptional()) {
                    $sequenceElement['nillable'] = 'true';
                }
                $sequence[] = $sequenceElement;
            }
            $element = array(
                'name' => $functionName,
                'sequence' => $sequence
            );
            // Add the wrapper element part, which must be named 'parameters'
            $args['parameters'] = array('element' => $wsdl->addElement($element));
        } else {
            // RPC style: add each parameter as a typed part
            foreach ($prototype->getParameters() as $param) {
                $args[$param->getName()] = array('type' => $wsdl->getType($this->_discoveryStrategy->getFunctionParameterType($param)));
            }
        }
        $wsdl->addMessage($functionName . 'In', $args);

        $isOneWayMessage = $this->_discoveryStrategy->isFunctionOneWay($function, $prototype);

        if($isOneWayMessage == false) {
            // Add the output message (return value)
            $args = array();
            if ($this->_bindingStyle['style'] == 'document') {
                // Document style: wrap the return value in a sequence element
                $sequence = array();
                if ($prototype->getReturnType() != "void") {
                    $sequence[] = array(
                        'name' => $functionName . 'Result',
                        'type' => $wsdl->getType($this->_discoveryStrategy->getFunctionReturnType($function, $prototype))
                    );
                }
                $element = array(
                    'name' => $functionName . 'Response',
                    'sequence' => $sequence
                );
                // Add the wrapper element part, which must be named 'parameters'
                $args['parameters'] = array('element' => $wsdl->addElement($element));
            } else if ($prototype->getReturnType() != "void") {
                // RPC style: add the return value as a typed part
                $args['return'] = array('type' => $wsdl->getType($this->_discoveryStrategy->getFunctionReturnType($function, $prototype)));
            }
            $wsdl->addMessage($functionName . 'Out', $args);
        }

        // Add the portType operation
        if($isOneWayMessage == false) {
            $portOperation = $wsdl->addPortOperation($port, $functionName, 'tns:' . $functionName . 'In', 'tns:' . $functionName . 'Out');
        } else {
            $portOperation = $wsdl->addPortOperation($port, $functionName, 'tns:' . $functionName . 'In', false);
        }
        $desc = $this->_discoveryStrategy->getFunctionDocumentation($function);
        if (strlen($desc) > 0) {
            $wsdl->addDocumentation($portOperation, $desc);
        }

        // When using the RPC style, make sure the operation style includes a 'namespace' attribute (WS-I Basic Profile 1.1 R2717)
        $operationBodyStyle = $this->_operationBodyStyle;
        if ($this->_bindingStyle['style'] == 'rpc' && !isset($operationBodyStyle['namespace'])) {
            $operationBodyStyle['namespace'] = ''.$uri;
        }

        // Add the binding operation
        if($isOneWayMessage == false) {
            $operation = $wsdl->addBindingOperation($binding, $functionName, $operationBodyStyle, $operationBodyStyle);
        } else {
            $operation = $wsdl->addBindingOperation($binding, $functionName, $operationBodyStyle);
        }
        $wsdl->addSoapOperation($operation, $uri . '#' . $functionName);
    }

    /**
     * Generate the WSDL file from the configured input.
     *
     * @return Zend_Wsdl
     */
    public function generate()
    {
        if ($this->_class && $this->_functions) {
            throw new Exception\RuntimeException("Can either dump functions or a class as a service, not both.");
        }

        if ($this->_class) {
            $wsdl = $this->_generateClass();
        } else {
            $wsdl = $this->_generateFunctions();
        }

        return $wsdl;
    }

    /**
     * Proxy to WSDL dump function
     *
     * @param string $filename
     * @return bool
     * @throws \Zend\Soap\Exception\RuntimeException
     */
    public function dump($filename)
    {
        return $this->generate()->dump($filename);
    }

    /**
     * Proxy to WSDL toXml() function
     *
     * @return string
     * @throws \Zend\Soap\Exception\RuntimeException
     */
    public function toXml()
    {
        return $this->generate()->toXml();
    }
}
