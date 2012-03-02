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
 * @package    Zend_Barcode
 * @subpackage Object
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Barcode\Object;

/**
 * Class for generate Ean5 barcode
 *
 * @category   Zend
 * @package    Zend_Barcode
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Ean5 extends Ean13
{

    protected $parities = array(
        0 => array('B','B','A','A','A'),
        1 => array('B','A','B','A','A'),
        2 => array('B','A','A','B','A'),
        3 => array('B','A','A','A','B'),
        4 => array('A','B','B','A','A'),
        5 => array('A','A','B','B','A'),
        6 => array('A','A','A','B','B'),
        7 => array('A','B','A','B','A'),
        8 => array('A','B','A','A','B'),
        9 => array('A','A','B','A','B')
    );

    /**
     * Default options for Ean5 barcode
     * @return void
     */
    protected function getDefaultOptions()
    {
        $this->barcodeLength = 5;
    }

    /**
     * Width of the barcode (in pixels)
     * @return integer
     */
    protected function calculateBarcodeWidth()
    {
        $quietZone       = $this->getQuietZone();
        $startCharacter  = (5 * $this->barThinWidth) * $this->factor;
        $middleCharacter = (2 * $this->barThinWidth) * $this->factor;
        $encodedData     = (7 * $this->barThinWidth) * $this->factor;
        return $quietZone + $startCharacter + ($this->barcodeLength - 1) * $middleCharacter + $this->barcodeLength * $encodedData + $quietZone;
    }

    /**
     * Prepare array to draw barcode
     * @return array
     */
    protected function prepareBarcode()
    {
        $barcodeTable = array();

        // Start character (01011)
        $barcodeTable[] = array(0 , $this->barThinWidth , 0 , 1);
        $barcodeTable[] = array(1 , $this->barThinWidth , 0 , 1);
        $barcodeTable[] = array(0 , $this->barThinWidth , 0 , 1);
        $barcodeTable[] = array(1 , $this->barThinWidth , 0 , 1);
        $barcodeTable[] = array(1 , $this->barThinWidth , 0 , 1);

        $firstCharacter = true;
        $textTable = str_split($this->getText());

        // Characters
        for ($i = 0; $i < $this->barcodeLength; $i++) {
            if ($firstCharacter) {
                $firstCharacter = false;
            } else {
                // Intermediate character (01)
                $barcodeTable[] = array(0 , $this->barThinWidth , 0 , 1);
                $barcodeTable[] = array(1 , $this->barThinWidth , 0 , 1);
            }
            $bars = str_split($this->codingMap[$this->getParity($i)][$textTable[$i]]);
            foreach ($bars as $b) {
                $barcodeTable[] = array($b , $this->barThinWidth , 0 , 1);
            }
        }

        return $barcodeTable;
    }

    /**
     * Get barcode checksum
     *
     * @param  string $text
     * @return int
     */
    public function getChecksum($text)
    {
        $this->checkText($text);
        $checksum = 0;

        for ($i = 0 ; $i < $this->barcodeLength; $i ++) {
            $checksum += intval($text{$i}) * ($i % 2 ? 9 : 3);
        }

        return ($checksum % 10);
    }

    protected function getParity($i)
    {
        $checksum = $this->getChecksum($this->getText());
        return $this->parities[$checksum][$i];
    }

    /**
     * Retrieve text to encode
     * @return string
     */
    public function getText()
    {
        return $this->addLeadingZeros($this->text);
    }
}
