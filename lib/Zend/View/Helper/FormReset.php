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
 * Helper to generate a "reset" button
 *
 * @uses       \Zend\View\Helper\FormElement
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class FormReset extends FormElement
{
    /**
     * Generates a 'reset' button.
     *
     * @access public
     *
     * @param string|array $name If a string, the element name.  If an
     * array, all other parameters are ignored, and the array elements
     * are extracted in place of added parameters.
     *
     * @param mixed $value The element value.
     *
     * @param array $attribs Attributes for the element tag.
     *
     * @return string The element XHTML.
     */
    public function __invoke($name = '', $value = 'Reset', $attribs = null)
    {
        $info = $this->_getInfo($name, $value, $attribs);
        extract($info); // name, value, attribs, options, listsep, disable

        // check if disabled
        $disabled = '';
        if ($disable) {
            $disabled = ' disabled="disabled"';
        }

        // get closing tag
        $endTag = '>';
        if ($this->view->plugin('doctype')->isXhtml()) {
            $endTag = ' />';
        }

        // Render button
        $escaper = $this->view->plugin('escape');
        $xhtml = '<input type="reset"'
               . ' name="' . $escaper($name) . '"'
               . ' id="'   . $escaper($id)   . '"'
               . $disabled;

        // add a value if one is given
        if (! empty($value)) {
            $xhtml .= ' value="' . $escaper($value) . '"';
        }

        // add attributes, close, and return
        $xhtml .= $this->_htmlAttribs($attribs) . $endTag;
        return $xhtml;
    }
}
