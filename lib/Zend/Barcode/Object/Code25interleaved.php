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
 * Class for generate Interleaved 2 of 5 barcode
 *
 * @category   Zend
 * @package    Zend_Barcode
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Code25interleaved extends Code25
{
    /**
     * Drawing of bearer bars
     * @var boolean
     */
    private $withBearerBars = false;

    /**
     * Default options for Code25interleaved barcode
     * @return void
     */
    protected function getDefaultOptions()
    {
        $this->barcodeLength = 'even';
    }

    /**
     * Activate/deactivate drawing of bearer bars
     * @param boolean $value
     * @return Code25
     */
    public function setWithBearerBars($value)
    {
        $this->withBearerBars = (bool) $value;
        return $this;
    }

    /**
     * Retrieve if bearer bars are enabled
     * @return boolean
     */
    public function getWithBearerBars()
    {
        return $this->withBearerBars;
    }

    /**
     * Width of the barcode (in pixels)
     * @return integer
     */
    protected function calculateBarcodeWidth()
    {
        $quietZone       = $this->getQuietZone();
        $startCharacter  = (4 * $this->barThinWidth) * $this->factor;
        $characterLength = (3 * $this->barThinWidth + 2 * $this->barThickWidth) * $this->factor;
        $encodedData     = strlen($this->getText()) * $characterLength;
        $stopCharacter   = ($this->barThickWidth + 2 * $this->barThinWidth) * $this->factor;
        return $quietZone + $startCharacter + $encodedData + $stopCharacter + $quietZone;
    }

    /**
     * Prepare array to draw barcode
     * @return array
     */
    protected function prepareBarcode()
    {
        if ($this->withBearerBars) {
            $this->withBorder = false;
        }

        // Start character (0000)
        $barcodeTable[] = array(1, $this->barThinWidth, 0, 1);
        $barcodeTable[] = array(0, $this->barThinWidth, 0, 1);
        $barcodeTable[] = array(1, $this->barThinWidth, 0, 1);
        $barcodeTable[] = array(0, $this->barThinWidth, 0, 1);

        // Encoded $text
        $text = $this->getText();
        for ($i = 0; $i < strlen($text); $i += 2) { // Draw 2 chars at a time
            $char1 = substr($text, $i, 1);
            $char2 = substr($text, $i + 1, 1);

            // Interleave
            for ($ibar = 0; $ibar < 5; $ibar ++) {
                // Draws char1 bar (fore color)
                $barWidth = (substr($this->codingMap[$char1], $ibar, 1))
                          ? $this->barThickWidth
                          : $this->barThinWidth;

                $barcodeTable[] = array(1, $barWidth, 0, 1);

                // Left space corresponding to char2 (background color)
                $barWidth = (substr($this->codingMap[$char2], $ibar, 1))
                          ? $this->barThickWidth
                          : $this->barThinWidth;
                $barcodeTable[] = array(0, $barWidth, 0 , 1);
            }
        }

        // Stop character (100)
        $barcodeTable[] = array(1 , $this->barThickWidth, 0, 1);
        $barcodeTable[] = array(0 , $this->barThinWidth,  0, 1);
        $barcodeTable[] = array(1 , $this->barThinWidth,  0, 1);
        return $barcodeTable;
    }

    /**
     * Drawing of bearer bars (if enabled)
     *
     * @return void
     */
    protected function postDrawBarcode()
    {
        if (!$this->withBearerBars) {
            return;
        }

        $width  = $this->barThickWidth * $this->factor;
        $point1 = $this->rotate(-1, -1);
        $point2 = $this->rotate($this->calculateWidth() - 1, -1);
        $point3 = $this->rotate($this->calculateWidth() - 1, $width - 1);
        $point4 = $this->rotate(-1, $width - 1);
        $this->addPolygon(array(
            $point1,
            $point2,
            $point3,
            $point4,
        ));
        $point1 = $this->rotate(
            0,
            0 + $this->barHeight * $this->factor - 1
        );
        $point2 = $this->rotate(
            $this->calculateWidth() - 1,
            0 + $this->barHeight * $this->factor - 1
        );
        $point3 = $this->rotate(
            $this->calculateWidth() - 1,
            0 + $this->barHeight * $this->factor - $width
        );
        $point4 = $this->rotate(
            0,
            0 + $this->barHeight * $this->factor - $width
        );
        $this->addPolygon(array(
            $point1,
            $point2,
            $point3,
            $point4,
        ));
    }
}
