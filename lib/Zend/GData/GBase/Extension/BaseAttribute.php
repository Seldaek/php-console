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
 * @package    Zend_Gdata
 * @subpackage GBase
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\GData\GBase\Extension;

/**
 * Concrete class for working with ItemType elements.
 *
 * @uses       \Zend\GData\App\Extension\Element
 * @uses       \Zend\GData\GBase
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage GBase
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class BaseAttribute extends \Zend\GData\App\Extension\Element
{

    /**
     * Namespace for Google Base elements
     *
     * var @string
     */
    protected $_rootNamespace = 'g';

    /**
     * Create a new instance.
     *
     * @param string $name (optional) The name of the Base attribute
     * @param string $text (optional) The text value of the Base attribute
     * @param string $text (optional) The type of the Base attribute
     */
    public function __construct($name = null, $text = null, $type = null)
    {
        $this->registerAllNamespaces(\Zend\GData\GBase::$namespaces);
        if ($type !== null) {
          $attr = array('name' => 'type', 'value' => $type);
          $typeAttr = array('type' => $attr);
          $this->setExtensionAttributes($typeAttr);
        }
        parent::__construct($name,
                            $this->_rootNamespace,
                            $this->lookupNamespace($this->_rootNamespace),
                            $text);
    }

    /**
     * Get the name of the attribute
     *
     * @return attribute name The requested object.
     */
    public function getName() {
      return $this->_rootElement;
    }

    /**
     * Get the type of the attribute
     *
     * @return attribute type The requested object.
     */
    public function getType() {
      $typeAttr = $this->getExtensionAttributes();
      return $typeAttr['type']['value'];
    }

    /**
     * Set the 'name' of the Base attribute object:
     *     &lt;g:[$name] type='[$type]'&gt;[$value]&lt;/g:[$name]&gt;
     *
     * @param \Zend\GData\App\Extension\Element $attribute The attribute object
     * @param string $name The name of the Base attribute
     * @return Zend_Gdata_Extension_ItemEntry Provides a fluent interface
     */
    public function setName($name) {
      $this->_rootElement = $name;
      return $this;
    }

    /**
     * Set the 'type' of the Base attribute object:
     *     &lt;g:[$name] type='[$type]'&gt;[$value]&lt;/g:[$name]&gt;
     *
     * @param \Zend\GData\App\Extension\Element $attribute The attribute object
     * @param string $type The type of the Base attribute
     * @return Zend_Gdata_Extension_ItemEntry Provides a fluent interface
     */
    public function setType($type) {
      $attr = array('name' => 'type', 'value' => $type);
      $typeAttr = array('type' => $attr);
      $this->setExtensionAttributes($typeAttr);
      return $this;
    }

}
