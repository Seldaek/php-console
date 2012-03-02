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
namespace Zend\Barcode;

/**
 * Interface for generate Barcode
 *
 * @category   Zend
 * @package    Zend_Barcode
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
interface Object
{
    /**
     * Constructor
     * @param array|Traversable $options
     * @return void
     */
    public function __construct($options = null);

    /**
     * Set barcode state from options array
     * @param  array $options
     * @return Object
     */
    public function setOptions($options);

    /**
     * Set barcode namespace for autoloading
     *
     * @param string $namespace
     * @return Object
     */
    public function setBarcodeNamespace($namespace);

    /**
     * Retrieve barcode namespace
     *
     * @return string
     */
    public function getBarcodeNamespace();

    /**
     * Retrieve type of barcode
     * @return string
     */
    public function getType();

    /**
     * Set height of the barcode bar
     * @param integer $value
     * @return Object
     */
    public function setBarHeight($value);

    /**
     * Get height of the barcode bar
     * @return integer
     */
    public function getBarHeight();

    /**
     * Set thickness of thin bar
     * @param integer $value
     * @return Object
     */
    public function setBarThinWidth($value);

    /**
     * Get thickness of thin bar
     * @return integer
     */
    public function getBarThinWidth();

    /**
     * Set thickness of thick bar
     * @param integer $value
     * @return Object
     */
    public function setBarThickWidth($value);

    /**
     * Get thickness of thick bar
     * @return integer
     */
    public function getBarThickWidth();

    /**
     * Set factor applying to
     * thinBarWidth - thickBarWidth - barHeight - fontSize
     * @param integer $value
     * @return Object
     */
    public function setFactor($value);

    /**
     * Get factor applying to
     * thinBarWidth - thickBarWidth - barHeight - fontSize
     * @return integer
     */
    public function getFactor();

    /**
     * Set color of the barcode and text
     * @param string $value
     * @return Object
     */
    public function setForeColor($value);

    /**
     * Retrieve color of the barcode and text
     * @return unknown
     */
    public function getForeColor();

    /**
     * Set the color of the background
     * @param integer $value
     * @return Object
     */
    public function setBackgroundColor($value);

    /**
     * Retrieve background color of the image
     * @return integer
     */
    public function getBackgroundColor();

    /**
     * Activate/deactivate drawing of the bar
     * @param boolean $value
     * @return Object
     */
    public function setWithBorder($value);

    /**
     * Retrieve if border are draw or not
     * @return boolean
     */
    public function getWithBorder();

    /**
     * Allow fast inversion of font/bars color and background color
     * @return Object
     */
    public function setReverseColor();

    /**
     * Set orientation of barcode and text
     * @param float $value
     * @return Object
     */
    public function setOrientation($value);

    /**
     * Retrieve orientation of barcode and text
     * @return float
     */
    public function getOrientation();

    /**
     * Set text to encode
     * @param string $value
     * @return Object
     */
    public function setText($value);

    /**
     * Retrieve text to encode
     * @return string
     */
    public function getText();

    /**
     * Retrieve text to encode
     * @return string
     */
    public function getRawText();

    /**
     * Retrieve text to display
     * @return string
     */
    public function getTextToDisplay();

    /**
     * Activate/deactivate drawing of text to encode
     * @param boolean $value
     * @return Object
     */
    public function setDrawText($value);

    /**
     * Retrieve if drawing of text to encode is enabled
     * @return boolean
     */
    public function getDrawText();

    /**
     * Activate/deactivate the adjustment of the position
     * of the characters to the position of the bars
     * @param boolean $value
     * @return Object
     */
    public function setStretchText($value);

    /**
     * Retrieve if the adjustment of the position of the characters
     * to the position of the bars is enabled
     * @return boolean
     */
    public function getStretchText();

    /**
     * Activate/deactivate the automatic generation
     * of the checksum character
     * added to the barcode text
     * @param boolean $value
     * @return Object
     */
    public function setWithChecksum($value);

    /**
     * Retrieve if the checksum character is automatically
     * added to the barcode text
     * @return boolean
     */
    public function getWithChecksum();

    /**
     * Activate/deactivate the automatic generation
     * of the checksum character
     * added to the barcode text
     * @param boolean $value
     * @return Object
     */
    public function setWithChecksumInText($value);

    /**
     * Retrieve if the checksum character is automatically
     * added to the barcode text
     * @return boolean
     */
    public function getWithChecksumInText();

    /**
     * Set the font:
     *  - if integer between 1 and 5, use gd built-in fonts
     *  - if string, $value is assumed to be the path to a TTF font
     * @param integer|string $value
     * @return Object
     */
    public function setFont($value);

    /**
     * Retrieve the font
     * @return integer|string
     */
    public function getFont();

    /**
     * Set the size of the font in case of TTF
     * @param float $value
     * @return Object
     */
    public function setFontSize($value);

    /**
     * Retrieve the size of the font in case of TTF
     * @return float
     */
    public function getFontSize();

    /**
     * Quiet zone before first bar
     * and after the last bar
     * @return integer
     */
    public function getQuietZone();

    /**
     * Retrieve the set of drawing instructions
     * @return array
     */
    public function getInstructions();

    /**
     * Checking of parameters after all settings
     * @return void
     */
    public function checkParams();

    /**
     * Get height of the result object
     * @return integer
     */
    public function getHeight($recalculate = false);

    /**
     * Get width of the result object
     * @return integer
     */
    public function getWidth($recalculate = false);

    /**
     * Calculate the offset from the left of the object
     * if an orientation is activated
     * @param boolean $recalculate
     * @return float
     */
    public function getOffsetLeft($recalculate = false);

    /**
     * Calculate the offset from the top of the object
     * if an orientation is activated
     * @param boolean $recalculate
     * @return float
     */
    public function getOffsetTop($recalculate = false);

    /**
     * Complete drawing of the barcode
     * @return array Table of instructions
     */
    public function draw();

    /**
     * Check for invalid characters
     * @param   string $value    Text to be ckecked
     * @return void
     */
    public function validateText($value);
}
