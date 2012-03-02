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
 * @package    Zend_InfoCard
 * @subpackage Zend_InfoCard_Xml
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\InfoCard\XML\EncryptedData;
use Zend\InfoCard\XML;

/**
 * A factory class for producing Zend_InfoCard_Xml_EncryptedData objects based on
 * the type of XML document provided
 *
 * @uses       \Zend\InfoCard\XML\EncryptedData\XMLEnc
 * @uses       \Zend\InfoCard\XML\Exception
 * @category   Zend
 * @package    Zend_InfoCard
 * @subpackage Zend_InfoCard_Xml
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
final class Factory
{
    /**
     * Constructor (disabled)
     *
     * @return void
     */
    private function __construct()
    {
    }

    /**
     * Returns an instance of the class
     *
     * @param string $xmlData The XML EncryptedData String
     * @return \Zend\InfoCard\XML\EncryptedData\AbstractEncryptedData
     * @throws \Zend\InfoCard\XML\Exception
     */
    static public function getInstance($xmlData)
    {

        if($xmlData instanceof XML\AbstractElement) {
            $strXmlData = $xmlData->asXML();
        } else if (is_string($xmlData)) {
            $strXmlData = $xmlData;
        } else {
            throw new XML\Exception\InvalidArgumentException("Invalid Data provided to create instance");
        }

        $sxe = simplexml_load_string($strXmlData);

        switch($sxe['Type']) {
            case 'http://www.w3.org/2001/04/xmlenc#Element':
                return simplexml_load_string($strXmlData, 'Zend\InfoCard\XML\EncryptedData\XMLEnc');
            default:
                throw new XML\Exception\InvalidArgumentException("Unknown EncryptedData type found");
                break;
        }
    }
}
