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
 * @subpackage WSDL
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Soap\Wsdl\ComplexTypeStrategy;

use Zend\Soap;

use Zend\Soap\Wsdl;
use Zend\Soap\Exception;

/**
 * ArrayOfTypeComplex strategy
 *
 * @uses       \Zend\Soap\Wsdl\Exception
 * @uses       \Zend\Soap\Wsdl\Strategy\DefaultComplexType
 * @category   Zend
 * @package    Zend_Soap
 * @subpackage WSDL
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class ArrayOfTypeComplex extends DefaultComplexType
{
    /**
     * Add an ArrayOfType based on the xsd:complexType syntax if type[] is detected in return value doc comment.
     *
     * @param string $type
     * @return string tns:xsd-type
     */
    public function addComplexType($type)
    {
        if (($soapType = $this->scanRegisteredTypes($type)) !== null) {
            return $soapType;
        }

        $singularType = $this->_getSingularPhpType($type);
        $nestingLevel = $this->_getNestedCount($type);

        if($nestingLevel == 0) {
            return parent::addComplexType($singularType);
        } else if($nestingLevel == 1) {
            // The following blocks define the Array of Object structure
            return $this->_addArrayOfComplexType($singularType, $type);
        } else {
            throw new Exception\InvalidArgumentException(
                'ArrayOfTypeComplex cannot return nested ArrayOfObject deeper than '
              . 'one level. Use array object properties to return deep nested data.'
            );
        }
    }

    /**
     * Add an ArrayOfType based on the xsd:complexType syntax if type[] is detected in return value doc comment.
     *
     * @param string $singularType   e.g. '\MyNamespace\MyClassname'
     * @param string $type           e.g. '\MyNamespace\MyClassname[]'
     * @return string tns:xsd-type   e.g. 'tns:ArrayOfMyNamespace.MyClassname'
     */
    protected function _addArrayOfComplexType($singularType, $type)
    {
        if (($soapType = $this->scanRegisteredTypes($type)) !== null) {
            return $soapType;
        }

        $xsdComplexTypeName = 'ArrayOf' . $this->getContext()->translateType($singularType);
        $xsdComplexType     = 'tns:' . $xsdComplexTypeName;

        // Register type here to avoid recursion
        $this->getContext()->addType($type, $xsdComplexType);

        // Process singular type using DefaultComplexType strategy
        parent::addComplexType($singularType);


        // Add array type structure to WSDL document
        $dom = $this->getContext()->toDomDocument();

        $complexType = $dom->createElement('xsd:complexType');
        $complexType->setAttribute('name', $xsdComplexTypeName);

        $complexContent = $dom->createElement('xsd:complexContent');
        $complexType->appendChild($complexContent);

        $xsdRestriction = $dom->createElement('xsd:restriction');
        $xsdRestriction->setAttribute('base', 'soap-enc:Array');
        $complexContent->appendChild($xsdRestriction);

        $xsdAttribute = $dom->createElement('xsd:attribute');
        $xsdAttribute->setAttribute('ref', 'soap-enc:arrayType');
        $xsdAttribute->setAttribute('wsdl:arrayType',
                                    'tns:' . $this->getContext()->translateType($singularType) . '[]');
        $xsdRestriction->appendChild($xsdAttribute);

        $this->getContext()->getSchema()->appendChild($complexType);

        return $xsdComplexType;
    }

    /**
     * From a nested definition with type[], get the singular PHP Type
     *
     * @param  string $type
     * @return string
     */
    protected function _getSingularPhpType($type)
    {
        return str_replace('[]', '', $type);
    }

    /**
     * Return the array nesting level based on the type name
     *
     * @param  string $type
     * @return integer
     */
    protected function _getNestedCount($type)
    {
        return substr_count($type, '[]');
    }
}
