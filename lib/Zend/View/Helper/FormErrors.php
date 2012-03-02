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
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\View\Helper;

/**
 * Helper to render errors for a form element
 *
 * @uses       \Zend\View\Helper\FormElement
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class FormErrors extends FormElement
{
    /**
     * @var \Zend\Form\Element\Element
     */
    protected $_element;

    /**#@+
     * @var string Element block start/end tags and separator
     */
    protected $_htmlElementEnd       = '</li></ul>';
    protected $_htmlElementStart     = '<ul%s><li>';
    protected $_htmlElementSeparator = '</li><li>';
    /**#@-*/

    /**
     * Render form errors
     *
     * @param  string|array $errors Error(s) to render
     * @param  array $options
     * @return string
     */
    public function __invoke($errors, array $options = null)
    {
        $escape = true;
        if (isset($options['escape'])) {
            $escape = (bool) $options['escape'];
            unset($options['escape']);
        }

        if (empty($options['class'])) {
            $options['class'] = 'errors';
        }

        $start = $this->getElementStart();
        if (strstr($start, '%s')) {
            $attribs = $this->_htmlAttribs($options);
            $start   = sprintf($start, $attribs);
        }

        if ($escape) {
            $escaper = $this->view->plugin('escape');
            foreach ($errors as $key => $error) {
                $errors[$key] = $escaper($error);
            }
        }

        $html  = $start
               . implode($this->getElementSeparator(), (array) $errors)
               . $this->getElementEnd();

        return $html;
    }

    /**
     * Set end string for displaying errors
     *
     * @param  string $string
     * @return \Zend\View\Helper\FormErrors
     */
    public function setElementEnd($string)
    {
        $this->_htmlElementEnd = (string) $string;
        return $this;
    }

    /**
     * Retrieve end string for displaying errors
     *
     * @return string
     */
    public function getElementEnd()
    {
        return $this->_htmlElementEnd;
    }

    /**
     * Set separator string for displaying errors
     *
     * @param  string $string
     * @return \Zend\View\Helper\FormErrors
     */
    public function setElementSeparator($string)
    {
        $this->_htmlElementSeparator = (string) $string;
        return $this;
    }

    /**
     * Retrieve separator string for displaying errors
     *
     * @return string
     */
    public function getElementSeparator()
    {
        return $this->_htmlElementSeparator;
    }

    /**
     * Set start string for displaying errors
     *
     * @param  string $string
     * @return \Zend\View\Helper\FormErrors
     */
    public function setElementStart($string)
    {
        $this->_htmlElementStart = (string) $string;
        return $this;
    }

    /**
     * Retrieve start string for displaying errors
     *
     * @return string
     */
    public function getElementStart()
    {
        return $this->_htmlElementStart;
    }

}
