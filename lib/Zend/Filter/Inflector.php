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
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Filter;

use Zend\Config,
    Zend\Loader\Broker;

/**
 * Filter chain for string inflection
 *
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Inflector extends AbstractFilter
{
    /**
     * @var \Zend\Loader\Broker
     */
    protected $_pluginBroker = null;

    /**
     * @var string
     */
    protected $_target = null;

    /**
     * @var bool
     */
    protected $_throwTargetExceptionsOn = true;

    /**
     * @var string
     */
    protected $_targetReplacementIdentifier = ':';

    /**
     * @var array
     */
    protected $_rules = array();

    /**
     * Constructor
     *
     * @param string|array $options Options to set
     */
    public function __construct($options = null)
    {
        if ($options instanceof Config\Config) {
            $options = $options->toArray();
        } else if (!is_array($options)) {
            $options = func_get_args();
            $temp    = array();

            if (!empty($options)) {
                $temp['target'] = array_shift($options);
            }

            if (!empty($options)) {
                $temp['rules'] = array_shift($options);
            }

            if (!empty($options)) {
                $temp['throwTargetExceptionsOn'] = array_shift($options);
            }

            if (!empty($options)) {
                $temp['targetReplacementIdentifier'] = array_shift($options);
            }

            $options = $temp;
        }

        $this->setOptions($options);
    }

    /**
     * Retreive plugin broker
     *
     * @return \Zend\Loader\Broker
     */
    public function getPluginBroker()
    {
        if (!$this->_pluginBroker instanceof Broker) {
            $this->setPluginBroker(new FilterBroker());
        }

        return $this->_pluginBroker;
    }

    /**
     * Set plugin broker
     *
     * @param \Zend\Loader\Broker $broker
     * @return \Zend\Filter\Inflector
     */
    public function setPluginBroker(Broker $broker)
    {
        $this->_pluginBroker = $broker;
        return $this;
    }

    /**
     * Use Zend_Config object to set object state
     *
     * @deprecated Use setOptions() instead
     * @param  \Zend\Config\Config $config
     * @return \Zend\Filter\Inflector
     */
    public function setConfig(Config\Config $config)
    {
        return $this->setOptions($config);
    }

    /**
     * Set options
     *
     * @param  array $options
     * @return \Zend\Filter\Inflector
     */
    public function setOptions($options)
    {
        if ($options instanceof Config\Config) {
            $options = $options->toArray();
        }

        // Set broker
        if (array_key_exists('pluginBroker', $options)) {
            if (is_scalar($options['pluginBroker']) && class_exists($options['pluginBroker'])) {
                $options['pluginBroker'] = new $options['pluginBroker'];
            }
            $this->setPluginBroker($options['pluginBroker']);
        }

        if (array_key_exists('throwTargetExceptionsOn', $options)) {
            $this->setThrowTargetExceptionsOn($options['throwTargetExceptionsOn']);
        }

        if (array_key_exists('targetReplacementIdentifier', $options)) {
            $this->setTargetReplacementIdentifier($options['targetReplacementIdentifier']);
        }

        if (array_key_exists('target', $options)) {
            $this->setTarget($options['target']);
        }

        if (array_key_exists('rules', $options)) {
            $this->addRules($options['rules']);
        }

        return $this;
    }

    /**
     * Set Whether or not the inflector should throw an exception when a replacement
     * identifier is still found within an inflected target.
     *
     * @param bool $throwTargetExceptions
     * @return \Zend\Filter\Inflector
     */
    public function setThrowTargetExceptionsOn($throwTargetExceptionsOn)
    {
        $this->_throwTargetExceptionsOn = ($throwTargetExceptionsOn == true) ? true : false;
        return $this;
    }

    /**
     * Will exceptions be thrown?
     *
     * @return bool
     */
    public function isThrowTargetExceptionsOn()
    {
        return $this->_throwTargetExceptionsOn;
    }

    /**
     * Set the Target Replacement Identifier, by default ':'
     *
     * @param string $targetReplacementIdentifier
     * @return \Zend\Filter\Inflector
     */
    public function setTargetReplacementIdentifier($targetReplacementIdentifier)
    {
        if ($targetReplacementIdentifier) {
            $this->_targetReplacementIdentifier = (string) $targetReplacementIdentifier;
        }

        return $this;
    }

    /**
     * Get Target Replacement Identifier
     *
     * @return string
     */
    public function getTargetReplacementIdentifier()
    {
        return $this->_targetReplacementIdentifier;
    }

    /**
     * Set a Target
     * ex: 'scripts/:controller/:action.:suffix'
     *
     * @param string
     * @return \Zend\Filter\Inflector
     */
    public function setTarget($target)
    {
        $this->_target = (string) $target;
        return $this;
    }

    /**
     * Retrieve target
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->_target;
    }

    /**
     * Set Target Reference
     *
     * @param reference $target
     * @return \Zend\Filter\Inflector
     */
    public function setTargetReference(&$target)
    {
        $this->_target =& $target;
        return $this;
    }

    /**
     * SetRules() is the same as calling addRules() with the exception that it
     * clears the rules before adding them.
     *
     * @param array $rules
     * @return \Zend\Filter\Inflector
     */
    public function setRules(Array $rules)
    {
        $this->clearRules();
        $this->addRules($rules);
        return $this;
    }

    /**
     * AddRules(): multi-call to setting filter rules.
     *
     * If prefixed with a ":" (colon), a filter rule will be added.  If not
     * prefixed, a static replacement will be added.
     *
     * ex:
     * array(
     *     ':controller' => array('CamelCaseToUnderscore','StringToLower'),
     *     ':action'     => array('CamelCaseToUnderscore','StringToLower'),
     *     'suffix'      => 'phtml'
     *     );
     *
     * @param array
     * @return \Zend\Filter\Inflector
     */
    public function addRules(Array $rules)
    {
        $keys = array_keys($rules);
        foreach ($keys as $spec) {
            if ($spec[0] == ':') {
                $this->addFilterRule($spec, $rules[$spec]);
            } else {
                $this->setStaticRule($spec, $rules[$spec]);
            }
        }

        return $this;
    }

    /**
     * Get rules
     *
     * By default, returns all rules. If a $spec is provided, will return those
     * rules if found, false otherwise.
     *
     * @param  string $spec
     * @return array|false
     */
    public function getRules($spec = null)
    {
        if (null !== $spec) {
            $spec = $this->_normalizeSpec($spec);
            if (isset($this->_rules[$spec])) {
                return $this->_rules[$spec];
            }
            return false;
        }

        return $this->_rules;
    }

    /**
     * getRule() returns a rule set by setFilterRule(), a numeric index must be provided
     *
     * @param string $spec
     * @param int $index
     * @return \Zend\Filter\Filter|false
     */
    public function getRule($spec, $index)
    {
        $spec = $this->_normalizeSpec($spec);
        if (isset($this->_rules[$spec]) && is_array($this->_rules[$spec])) {
            if (isset($this->_rules[$spec][$index])) {
                return $this->_rules[$spec][$index];
            }
        }
        return false;
    }

    /**
     * ClearRules() clears the rules currently in the inflector
     *
     * @return \Zend\Filter\Inflector
     */
    public function clearRules()
    {
        $this->_rules = array();
        return $this;
    }

    /**
     * Set a filtering rule for a spec.  $ruleSet can be a string, Filter object
     * or an array of strings or filter objects.
     *
     * @param string $spec
     * @param array|string|\Zend\Filter\Filter $ruleSet
     * @return \Zend\Filter\Inflector
     */
    public function setFilterRule($spec, $ruleSet)
    {
        $spec = $this->_normalizeSpec($spec);
        $this->_rules[$spec] = array();
        return $this->addFilterRule($spec, $ruleSet);
    }

    /**
     * Add a filter rule for a spec
     *
     * @param mixed $spec
     * @param mixed $ruleSet
     * @return void
     */
    public function addFilterRule($spec, $ruleSet)
    {
        $spec = $this->_normalizeSpec($spec);
        if (!isset($this->_rules[$spec])) {
            $this->_rules[$spec] = array();
        }

        if (!is_array($ruleSet)) {
            $ruleSet = array($ruleSet);
        }

        if (is_string($this->_rules[$spec])) {
            $temp = $this->_rules[$spec];
            $this->_rules[$spec] = array();
            $this->_rules[$spec][] = $temp;
        }

        foreach ($ruleSet as $rule) {
            $this->_rules[$spec][] = $this->_getRule($rule);
        }

        return $this;
    }

    /**
     * Set a static rule for a spec.  This is a single string value
     *
     * @param string $name
     * @param string $value
     * @return \Zend\Filter\Inflector
     */
    public function setStaticRule($name, $value)
    {
        $name = $this->_normalizeSpec($name);
        $this->_rules[$name] = (string) $value;
        return $this;
    }

    /**
     * Set Static Rule Reference.
     *
     * This allows a consuming class to pass a property or variable
     * in to be referenced when its time to build the output string from the
     * target.
     *
     * @param string $name
     * @param mixed $reference
     * @return \Zend\Filter\Inflector
     */
    public function setStaticRuleReference($name, &$reference)
    {
        $name = $this->_normalizeSpec($name);
        $this->_rules[$name] =& $reference;
        return $this;
    }

    /**
     * Inflect
     *
     * @param  string|array $source
     * @return string
     */
    public function filter($source)
    {
        // clean source
        foreach ( (array) $source as $sourceName => $sourceValue) {
            $source[ltrim($sourceName, ':')] = $sourceValue;
        }

        $pregQuotedTargetReplacementIdentifier = preg_quote($this->_targetReplacementIdentifier, '#');
        $processedParts = array();

        foreach ($this->_rules as $ruleName => $ruleValue) {
            if (isset($source[$ruleName])) {
                if (is_string($ruleValue)) {
                    // overriding the set rule
                    $processedParts['#'.$pregQuotedTargetReplacementIdentifier.$ruleName.'#'] = str_replace('\\', '\\\\', $source[$ruleName]);
                } elseif (is_array($ruleValue)) {
                    $processedPart = $source[$ruleName];
                    foreach ($ruleValue as $ruleFilter) {
                        $processedPart = $ruleFilter($processedPart);
                    }
                    $processedParts['#'.$pregQuotedTargetReplacementIdentifier.$ruleName.'#'] = str_replace('\\', '\\\\', $processedPart);
                }
            } elseif (is_string($ruleValue)) {
                $processedParts['#'.$pregQuotedTargetReplacementIdentifier.$ruleName.'#'] = str_replace('\\', '\\\\', $ruleValue);
            }
        }

        // all of the values of processedParts would have been str_replace('\\', '\\\\', ..)'d to disable preg_replace backreferences
        $inflectedTarget = preg_replace(array_keys($processedParts), array_values($processedParts), $this->_target);

        if ($this->_throwTargetExceptionsOn && (preg_match('#(?='.$pregQuotedTargetReplacementIdentifier.'[A-Za-z]{1})#', $inflectedTarget) == true)) {
            throw new Exception\RuntimeException('A replacement identifier ' . $this->_targetReplacementIdentifier . ' was found inside the inflected target, perhaps a rule was not satisfied with a target source?  Unsatisfied inflected target: ' . $inflectedTarget);
        }

        return $inflectedTarget;
    }

    /**
     * Normalize spec string
     *
     * @param  string $spec
     * @return string
     */
    protected function _normalizeSpec($spec)
    {
        return ltrim((string) $spec, ':&');
    }

    /**
     * Resolve named filters and convert them to filter objects.
     *
     * @param  string $rule
     * @return \Zend\Filter\Filter
     */
    protected function _getRule($rule)
    {
        if ($rule instanceof Filter) {
            return $rule;
        }

        $rule = (string) $rule;
        return $this->getPluginBroker()->load($rule);
    }
}
