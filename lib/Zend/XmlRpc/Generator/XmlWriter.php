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
 * @package    Zend_XmlRpc
 * @subpackage Generator
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\XmlRpc\Generator;

/**
 * XML generator adapter based on XMLWriter
 *
 * @uses       XMLWriter
 * @uses       Zend\XmlRpc\Generator\AbstractGenerator
 * @category   Zend
 * @package    Zend_XmlRpc
 * @subpackage Generator
 */
class XmlWriter extends AbstractGenerator
{
    /**
     * XMLWriter instance
     *
     * @var XMLWriter
     */
    protected $_xmlWriter;

    /**
     * Initialized XMLWriter instance
     *
     * @return void
     */
    protected function _init()
    {
        $this->_xmlWriter = new \XMLWriter();
        $this->_xmlWriter->openMemory();
        $this->_xmlWriter->startDocument('1.0', $this->_encoding);
    }


    /**
     * Open a new XML element
     *
     * @param string $name XML element name
     * @return void
     */
    protected function _openElement($name)
    {
        $this->_xmlWriter->startElement($name);
    }

    /**
     * Write XML text data into the currently opened XML element
     *
     * @param string $text XML text data
     * @return void
     */
    protected function _writeTextData($text)
    {
        $this->_xmlWriter->text($text);
    }

    /**
     * Close an previously opened XML element
     *
     * @param string $name
     * @return void
     */
    protected function _closeElement($name)
    {
        $this->_xmlWriter->endElement();

        return $this;
    }

    /**
     * Emit XML document
     * 
     * @return string
     */
    public function saveXml()
    {
        return $this->_xmlWriter->flush(false);
    }
}
