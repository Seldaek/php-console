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
 * @package    Zend_Validate
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Validator;

/**
 * @uses       \Zend\Validator\AbstractValidator
 * @uses       \Zend\Validator\Exception
 * @category   Zend
 * @package    Zend_Validate
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Between extends AbstractValidator
{
    const NOT_BETWEEN        = 'notBetween';
    const NOT_BETWEEN_STRICT = 'notBetweenStrict';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = array(
        self::NOT_BETWEEN        => "'%value%' is not between '%min%' and '%max%', inclusively",
        self::NOT_BETWEEN_STRICT => "'%value%' is not strictly between '%min%' and '%max%'"
    );

    /**
     * Additional variables available for validation failure messages
     *
     * @var array
     */
    protected $_messageVariables = array(
        'min' => array('options' => 'min'),
        'max' => array('options' => 'max'),
    );

    /**
     * Options for the between validator
     *
     * @var array
     */
    protected $options = array(
        'inclusive' => true,  // Whether to do inclusive comparisons, allowing equivalence to min and/or max
        'min'       => 0,
        'max'       => PHP_INT_MAX,
    );

    /**
     * Sets validator options
     * Accepts the following option keys:
     *   'min' => scalar, minimum border
     *   'max' => scalar, maximum border
     *   'inclusive' => boolean, inclusive border values
     *
     * @param  array|\Zend\Config\Config $options
     * @return void
     */
    public function __construct($options = null)
    {
        if ($options instanceof \Zend\Config\Config) {
            $options = $options->toArray();
        } else if (!is_array($options)) {
            $options = func_get_args();
            $temp['min'] = array_shift($options);
            if (!empty($options)) {
                $temp['max'] = array_shift($options);
            }

            if (!empty($options)) {
                $temp['inclusive'] = array_shift($options);
            }

            $options = $temp;
        }

        if (!array_key_exists('min', $options) || !array_key_exists('max', $options)) {
//            throw new Exception\InvalidArgumentException("Missing option. 'min' and 'max' has to be given");
        }

        parent::__construct($options);
    }

    /**
     * Returns the min option
     *
     * @return mixed
     */
    public function getMin()
    {
        return $this->options['min'];
    }

    /**
     * Sets the min option
     *
     * @param  mixed $min
     * @return \Zend\Validator\Between Provides a fluent interface
     */
    public function setMin($min)
    {
        $this->options['min'] = $min;
        return $this;
    }

    /**
     * Returns the max option
     *
     * @return mixed
     */
    public function getMax()
    {
        return $this->options['max'];
    }

    /**
     * Sets the max option
     *
     * @param  mixed $max
     * @return \Zend\Validator\Between Provides a fluent interface
     */
    public function setMax($max)
    {
        $this->options['max'] = $max;
        return $this;
    }

    /**
     * Returns the inclusive option
     *
     * @return boolean
     */
    public function getInclusive()
    {
        return $this->options['inclusive'];
    }

    /**
     * Sets the inclusive option
     *
     * @param  boolean $inclusive
     * @return \Zend\Validator\Between Provides a fluent interface
     */
    public function setInclusive($inclusive)
    {
        $this->options['inclusive'] = $inclusive;
        return $this;
    }

    /**
     * Returns true if and only if $value is between min and max options, inclusively
     * if inclusive option is true.
     *
     * @param  mixed $value
     * @return boolean
     */
    public function isValid($value)
    {
        $this->setValue($value);

        if ($this->getInclusive()) {
            if ($this->getMin() > $value || $value > $this->getMax()) {
                $this->error(self::NOT_BETWEEN);
                return false;
            }
        } else {
            if ($this->getMin() >= $value || $value >= $this->getMax()) {
                $this->error(self::NOT_BETWEEN_STRICT);
                return false;
            }
        }

        return true;
    }
}
