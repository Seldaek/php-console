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
 * @package    Zend_Server
 * @subpackage Zend_Server_Reflection
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Server\Reflection;

/**
 * Function/Method Reflection
 *
 * Decorates a ReflectionFunction. Allows setting and retrieving an alternate
 * 'service' name (i.e., the name to be used when calling via a service),
 * setting and retrieving the description (originally set using the docblock
 * contents), retrieving the callback and callback type, retrieving additional
 * method invocation arguments, and retrieving the
 * method {@link \Zend\Server\Reflection\Prototype prototypes}.
 *
 * @uses       ReflectionClass
 * @uses       ReflectionFunction
 * @uses       ReflectionMethod
 * @uses       \Zend\Server\Reflection\Exception
 * @uses       \Zend\Server\Reflection\Node
 * @uses       \Zend\Server\Reflection\ReflectionParameter
 * @uses       \Zend\Server\Reflection\Prototype
 * @uses       \Zend\Server\Reflection\ReflectionReturnValue
 * @category   Zend
 * @package    Zend_Server
 * @subpackage Zend_Server_Reflection
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class AbstractFunction
{
    /**
     * @var ReflectionFunction
     */
    protected $_reflection;

    /**
     * Additional arguments to pass to method on invocation
     * @var array
     */
    protected $_argv = array();

    /**
     * Used to store extra configuration for the method (typically done by the
     * server class, e.g., to indicate whether or not to instantiate a class).
     * Associative array; access is as properties via {@link __get()} and
     * {@link __set()}
     * @var array
     */
    protected $_config = array();

    /**
     * Declaring class (needed for when serialization occurs)
     * @var string
     */
    protected $_class;

    /**
     * Function/method description
     * @var string
     */
    protected $_description = '';

    /**
     * Namespace with which to prefix function/method name
     * @var string
     */
    protected $_namespace;

    /**
     * Prototypes
     * @var array
     */
    protected $_prototypes = array();

    private $_return;
    private $_returnDesc;
    private $_paramDesc;
    private $_sigParams;
    private $_sigParamsDepth;

    /**
     * Constructor
     *
     * @param ReflectionFunction $r
     */
    public function __construct(\Reflector $r, $namespace = null, $argv = array())
    {
        // In PHP 5.1.x, ReflectionMethod extends ReflectionFunction. In 5.2.x,
        // both extend ReflectionFunctionAbstract. So, we can't do normal type
        // hinting in the prototype, but instead need to do some explicit
        // testing here.
        if ((!$r instanceof \ReflectionFunction)
            && (!$r instanceof \ReflectionMethod)) {
            throw new Exception\InvalidArgumentException('Invalid reflection class');
        }
        $this->_reflection = $r;

        // Determine namespace
        if (null !== $namespace){
            $this->setNamespace($namespace);
        }

        // Determine arguments
        if (is_array($argv)) {
            $this->_argv = $argv;
        }

        // If method call, need to store some info on the class
        if ($r instanceof \ReflectionMethod) {
            $this->_class = $r->getDeclaringClass()->getName();
        }

        // Perform some introspection
        $this->_reflect();
    }

    /**
     * Create signature node tree
     *
     * Recursive method to build the signature node tree. Increments through
     * each array in {@link $_sigParams}, adding every value of the next level
     * to the current value (unless the current value is null).
     *
     * @param \Zend\Server\Reflection\Node $parent
     * @param int $level
     * @return void
     */
    protected function _addTree(Node $parent, $level = 0)
    {
        if ($level >= $this->_sigParamsDepth) {
            return;
        }

        foreach ($this->_sigParams[$level] as $value) {
            $node = new Node($value, $parent);
            if ((null !== $value) && ($this->_sigParamsDepth > $level + 1)) {
                $this->_addTree($node, $level + 1);
            }
        }
    }

    /**
     * Build the signature tree
     *
     * Builds a signature tree starting at the return values and descending
     * through each method argument. Returns an array of
     * {@link \Zend\Server\Reflection\Node}s.
     *
     * @return array
     */
    protected function _buildTree()
    {
        $returnTree = array();
        foreach ((array) $this->_return as $value) {
            $node = new Node($value);
            $this->_addTree($node);
            $returnTree[] = $node;
        }

        return $returnTree;
    }

    /**
     * Build method signatures
     *
     * Builds method signatures using the array of return types and the array of
     * parameters types
     *
     * @param array $return Array of return types
     * @param string $returnDesc Return value description
     * @param array $params Array of arguments (each an array of types)
     * @param array $paramDesc Array of parameter descriptions
     * @return array
     */
    protected function _buildSignatures($return, $returnDesc, $paramTypes, $paramDesc)
    {
        $this->_return         = $return;
        $this->_returnDesc     = $returnDesc;
        $this->_paramDesc      = $paramDesc;
        $this->_sigParams      = $paramTypes;
        $this->_sigParamsDepth = count($paramTypes);
        $signatureTrees        = $this->_buildTree();
        $signatures            = array();

        $endPoints = array();
        foreach ($signatureTrees as $root) {
            $tmp = $root->getEndPoints();
            if (empty($tmp)) {
                $endPoints = array_merge($endPoints, array($root));
            } else {
                $endPoints = array_merge($endPoints, $tmp);
            }
        }

        foreach ($endPoints as $node) {
            if (!$node instanceof Node) {
                continue;
            }

            $signature = array();
            do {
                array_unshift($signature, $node->getValue());
                $node = $node->getParent();
            } while ($node instanceof Node);

            $signatures[] = $signature;
        }

        // Build prototypes
        $params = $this->_reflection->getParameters();
        foreach ($signatures as $signature) {
            $return = new ReflectionReturnValue(array_shift($signature), $this->_returnDesc);
            $tmp    = array();
            foreach ($signature as $key => $type) {
                $param = new ReflectionParameter($params[$key], $type, (isset($this->_paramDesc[$key]) ? $this->_paramDesc[$key] : null));
                $param->setPosition($key);
                $tmp[] = $param;
            }

            $this->_prototypes[] = new Prototype($return, $tmp);
        }
    }

    /**
     * Use code reflection to create method signatures
     *
     * Determines the method help/description text from the function DocBlock
     * comment. Determines method signatures using a combination of
     * ReflectionFunction and parsing of DocBlock @param and @return values.
     *
     * @param ReflectionFunction $function
     * @return array
     */
    protected function _reflect()
    {
        $function           = $this->_reflection;
        $helpText           = '';
        $signatures         = array();
        $returnDesc         = '';
        $paramCount         = $function->getNumberOfParameters();
        $paramCountRequired = $function->getNumberOfRequiredParameters();
        $parameters         = $function->getParameters();
        $docBlock           = $function->getDocComment();

        if (!empty($docBlock)) {
            // Get help text
            if (preg_match(':/\*\*\s*\r?\n\s*\*\s(.*?)\r?\n\s*\*(\s@|/):s', $docBlock, $matches))
            {
                $helpText = $matches[1];
                $helpText = preg_replace('/(^\s*\*\s)/m', '', $helpText);
                $helpText = preg_replace('/\r?\n\s*\*\s*(\r?\n)*/s', "\n", $helpText);
                $helpText = trim($helpText);
            }

            // Get return type(s) and description
            $return     = 'void';
            if (preg_match('/@return\s+(\S+)/', $docBlock, $matches)) {
                $return = explode('|', $matches[1]);
                if (preg_match('/@return\s+\S+\s+(.*?)(@|\*\/)/s', $docBlock, $matches))
                {
                    $value = $matches[1];
                    $value = preg_replace('/\s?\*\s/m', '', $value);
                    $value = preg_replace('/\s{2,}/', ' ', $value);
                    $returnDesc = trim($value);
                }
            }

            // Get param types and description
            if (preg_match_all('/@param\s+([^\s]+)/m', $docBlock, $matches)) {
                $paramTypesTmp = $matches[1];
                if (preg_match_all('/@param\s+\S+\s+(\$\S+)\s+(.*?)(@|\*\/)/s', $docBlock, $matches))
                {
                    $paramDesc = $matches[2];
                    foreach ($paramDesc as $key => $value) {
                        $value = preg_replace('/\s?\*\s/m', '', $value);
                        $value = preg_replace('/\s{2,}/', ' ', $value);
                        $paramDesc[$key] = trim($value);
                    }
                }
            }
        } else {
            $helpText = $function->getName();
            $return   = 'void';
        }

        // Set method description
        $this->setDescription($helpText);

        // Get all param types as arrays
        if (!isset($paramTypesTmp) && (0 < $paramCount)) {
            $paramTypesTmp = array_fill(0, $paramCount, 'mixed');
        } elseif (!isset($paramTypesTmp)) {
            $paramTypesTmp = array();
        } elseif (count($paramTypesTmp) < $paramCount) {
            $start = $paramCount - count($paramTypesTmp);
            for ($i = $start; $i < $paramCount; ++$i) {
                $paramTypesTmp[$i] = 'mixed';
            }
        }

        // Get all param descriptions as arrays
        if (!isset($paramDesc) && (0 < $paramCount)) {
            $paramDesc = array_fill(0, $paramCount, '');
        } elseif (!isset($paramDesc)) {
            $paramDesc = array();
        } elseif (count($paramDesc) < $paramCount) {
            $start = $paramCount - count($paramDesc);
            for ($i = $start; $i < $paramCount; ++$i) {
                $paramDesc[$i] = '';
            }
        }

        if (count($paramTypesTmp) != $paramCount) {
            throw new Exception\RuntimeException(
               'Variable number of arguments is not supported for services (except optional parameters). '
             . 'Number of function arguments must correspond to actual number of arguments described in a docblock.');
        }

        $paramTypes = array();
        foreach ($paramTypesTmp as $i => $param) {
            $tmp = explode('|', $param);
            if ($parameters[$i]->isOptional()) {
                array_unshift($tmp, null);
            }
            $paramTypes[] = $tmp;
        }

        $this->_buildSignatures($return, $returnDesc, $paramTypes, $paramDesc);
    }


    /**
     * Proxy reflection calls
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (method_exists($this->_reflection, $method)) {
            return call_user_func_array(array($this->_reflection, $method), $args);
        }

        throw new Exception\BadMethodCallException('Invalid reflection method ("' .$method. '")');
    }

    /**
     * Retrieve configuration parameters
     *
     * Values are retrieved by key from {@link $_config}. Returns null if no
     * value found.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (isset($this->_config[$key])) {
            return $this->_config[$key];
        }

        return null;
    }

    /**
     * Set configuration parameters
     *
     * Values are stored by $key in {@link $_config}.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->_config[$key] = $value;
    }

    /**
     * Set method's namespace
     *
     * @param string $namespace
     * @return void
     */
    public function setNamespace($namespace)
    {
        if (empty($namespace)) {
            $this->_namespace = '';
            return;
        }

        if (!is_string($namespace) || !preg_match('/[a-z0-9_\.]+/i', $namespace)) {
            throw new Exception\InvalidArgumentException('Invalid namespace');
        }

        $this->_namespace = $namespace;
    }

    /**
     * Return method's namespace
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->_namespace;
    }

    /**
     * Set the description
     *
     * @param string $string
     * @return void
     */
    public function setDescription($string)
    {
        if (!is_string($string)) {
            throw new Exception\InvalidArgumentException('Invalid description');
        }

        $this->_description = $string;
    }

    /**
     * Retrieve the description
     *
     * @return void
     */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
     * Retrieve all prototypes as array of
     * {@link \Zend\Server\Reflection\Prototype}s
     *
     * @return array
     */
    public function getPrototypes()
    {
        return $this->_prototypes;
    }

    /**
     * Retrieve additional invocation arguments
     *
     * @return array
     */
    public function getInvokeArguments()
    {
        return $this->_argv;
    }

    /**
     * Wakeup from serialization
     *
     * Reflection needs explicit instantiation to work correctly. Re-instantiate
     * reflection object on wakeup.
     *
     * @return void
     */
    public function __wakeup()
    {
        if ($this->_reflection instanceof \ReflectionMethod) {
            $class = new \ReflectionClass($this->_class);
            $this->_reflection = new \ReflectionMethod($class->newInstance(), $this->getName());
        } else {
            $this->_reflection = new \ReflectionFunction($this->getName());
        }
    }
}
