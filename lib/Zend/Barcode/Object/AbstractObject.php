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

use Traversable,
    Zend\Barcode,
    Zend\Barcode\Object\Exception,
    Zend\Validator\Barcode as BarcodeValidator,
    Zend\Stdlib\IteratorToArray;

/**
 * Class for generate Barcode
 *
 * @category   Zend
 * @package    Zend_Barcode
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class AbstractObject implements Barcode\Object
{
    /**
     * Namespace of the barcode for autoloading
     * @var string
     */
    protected $barcodeNamespace = 'Zend\Barcode\Object';

    /**
     * Set of drawing instructions
     * @var array
     */
    protected $instructions = array();

    /**
     * Barcode type
     * @var string
     */
    protected $type = null;

    /**
     * Height of the object
     * @var integer
     */
    protected $height = null;

    /**
     * Width of the object
     * @var integer
     */
    protected $width = null;

    /**
     * Height of the bar
     * @var integer
     */
    protected $barHeight = 50;

    /**
     * Width of a thin bar
     * @var integer
     */
    protected $barThinWidth = 1;

    /**
     * Width of a thick bar
     * @var integer
     */
    protected $barThickWidth = 3;

    /**
     * Factor to multiply bar and font measure
     * (barHeight, barThinWidth, barThickWidth & fontSize)
     * @var integer
     */
    protected $factor = 1;

    /**
     * Font and bars color of the object
     * @var integer
     */
    protected $foreColor = 0x000000;

    /**
     * Background color of the object
     * @var integer
     */
    protected $backgroundColor = 0xFFFFFF;

    /**
     * Activate/deactivate border of the object
     * @var boolean
     */
    protected $withBorder = false;

    /**
     * Activate/deactivate drawing of quiet zones
     * @var boolean
     */
    protected $withQuietZones = true;

    /**
     * Force quiet zones even if
     * @var boolean
     */
    protected $mandatoryQuietZones = false;

    /**
     * Orientation of the barcode in degrees
     * @var float
     */
    protected $orientation = 0;

    /**
     * Offset from the top the object
     * (calculated from the orientation)
     * @var integer
     */
    protected $offsetTop = null;

    /**
     * Offset from the left the object
     * (calculated from the orientation)
     * @var integer
     */
    protected $offsetLeft = null;

    /**
     * Text to display
     * @var string
     */
    protected $text = null;

    /**
     * Display (or not) human readable text
     * @var boolean
     */
    protected $drawText = true;

    /**
     * Adjust (or not) position of human readable characters with barcode
     * @var boolean
     */
    protected $stretchText = false;

    /**
     * Font resource
     *  - integer (1 to 5): corresponds to GD included fonts
     *  - string: corresponds to path of a TTF font
     * @var integer|string
     */
    protected $font = null;

    /**
     * Font size
     * @var float
     */
    protected $fontSize = 10;

    /**
     * Drawing of checksum
     * @var boolean
     */
    protected $withChecksum = false;

    /**
     * Drawing of checksum inside text
     * @var boolean
     */
    protected $withChecksumInText = false;

    /**
     * Fix barcode length (numeric or string like 'even')
     * @var $_barcodeLength integer | string
     */
    protected $barcodeLength = null;

    /**
     * Activate automatic addition of leading zeros
     * if barcode length is fixed
     * @var $_addLeadingZeros boolean
     */
    protected $addLeadingZeros = true;

    /**
     * Activation of mandatory checksum
     * to deactivate unauthorized modification
     * @var $_mandatoryChecksum boolean
     */
    protected $mandatoryChecksum = false;

    /**
     * Character used to substitute checksum character for validation
     * @var $_substituteChecksumCharacter mixed
     */
    protected $substituteChecksumCharacter = 0;

    /**
     * Constructor
     * @param array|Traversable $options
     * @return void
     */
    public function __construct($options = null)
    {
        $this->getDefaultOptions();
        $this->font = Barcode\Barcode::getBarcodeFont();
        if ($options instanceof Traversable) {
            $options = IteratorToArray::convert($options);
        }
        if (is_array($options)) {
            $this->setOptions($options);
        }
        $this->type = strtolower(substr(get_class($this), strlen($this->barcodeNamespace) + 1));
        if ($this->mandatoryChecksum) {
            $this->withChecksum = true;
            $this->withChecksumInText = true;
        }
    }

    /**
     * Set default options for particular object
     * @return void
     */
    protected function getDefaultOptions()
    {
    }

    /**
     * Set barcode state from options array
     * @param  array $options
     * @return \Zend\Barcode\Object
     */
    public function setOptions($options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . $key;
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    /**
     * Set barcode namespace for autoloading
     *
     * @param string $namespace
     * @return \Zend\Barcode\Object
     */
    public function setBarcodeNamespace($namespace)
    {
        $this->barcodeNamespace = $namespace;
        return $this;
    }

    /**
     * Retrieve barcode namespace
     *
     * @return string
     */
    public function getBarcodeNamespace()
    {
        return $this->barcodeNamespace;
    }

    /**
     * Retrieve type of barcode
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set height of the barcode bar
     * @param integer $value
     * @return \Zend\Barcode\Object
     * @throw \Zend\Barcode\Object\Exception
     */
    public function setBarHeight($value)
    {
        if (intval($value) <= 0) {
            throw new Exception\OutOfRangeException(
                'Bar height must be greater than 0'
            );
        }
        $this->barHeight = intval($value);
        return $this;
    }

    /**
     * Get height of the barcode bar
     * @return integer
     */
    public function getBarHeight()
    {
        return $this->barHeight;
    }

    /**
     * Set thickness of thin bar
     * @param integer $value
     * @return \Zend\Barcode\Object
     * @throw \Zend\Barcode\Object\Exception
     */
    public function setBarThinWidth($value)
    {
        if (intval($value) <= 0) {
            throw new Exception\OutOfRangeException(
                'Bar width must be greater than 0'
            );
        }
        $this->barThinWidth = intval($value);
        return $this;
    }

    /**
     * Get thickness of thin bar
     * @return integer
     */
    public function getBarThinWidth()
    {
        return $this->barThinWidth;
    }

    /**
     * Set thickness of thick bar
     * @param integer $value
     * @return \Zend\Barcode\Object
     * @throw \Zend\Barcode\Object\Exception
     */
    public function setBarThickWidth($value)
    {
        if (intval($value) <= 0) {
            throw new Exception\OutOfRangeException(
                'Bar width must be greater than 0'
            );
        }
        $this->barThickWidth = intval($value);
        return $this;
    }

    /**
     * Get thickness of thick bar
     * @return integer
     */
    public function getBarThickWidth()
    {
        return $this->barThickWidth;
    }

    /**
     * Set factor applying to
     * thinBarWidth - thickBarWidth - barHeight - fontSize
     * @param float $value
     * @return \Zend\Barcode\Object
     * @throw \Zend\Barcode\Object\Exception
     */
    public function setFactor($value)
    {
        if (floatval($value) <= 0) {
            throw new Exception\OutOfRangeException(
                'Factor must be greater than 0'
            );
        }
        $this->factor = floatval($value);
        return $this;
    }

    /**
     * Get factor applying to
     * thinBarWidth - thickBarWidth - barHeight - fontSize
     * @return integer
     */
    public function getFactor()
    {
        return $this->factor;
    }

    /**
     * Set color of the barcode and text
     * @param string $value
     * @return \Zend\Barcode\Object
     * @throw \Zend\Barcode\Object\Exception
     */
    public function setForeColor($value)
    {
        if (preg_match('`\#[0-9A-F]{6}`', $value)) {
            $this->foreColor = hexdec($value);
        } elseif (is_numeric($value) && $value >= 0 && $value <= 16777125) {
            $this->foreColor = intval($value);
        } else {
            throw new Exception\InvalidArgumentException(
                'Text color must be set as #[0-9A-F]{6}'
            );
        }
        return $this;
    }

    /**
     * Retrieve color of the barcode and text
     * @return unknown
     */
    public function getForeColor()
    {
        return $this->foreColor;
    }

    /**
     * Set the color of the background
     * @param integer $value
     * @return \Zend\Barcode\Object
     * @throw \Zend\Barcode\Object\Exception
     */
    public function setBackgroundColor($value)
    {
        if (preg_match('`\#[0-9A-F]{6}`', $value)) {
            $this->backgroundColor = hexdec($value);
        } elseif (is_numeric($value) && $value >= 0 && $value <= 16777125) {
            $this->backgroundColor = intval($value);
        } else {
            throw new Exception\InvalidArgumentException(
                'Background color must be set as #[0-9A-F]{6}'
            );
        }
        return $this;
    }

    /**
     * Retrieve background color of the image
     * @return integer
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * Activate/deactivate drawing of the bar
     * @param boolean $value
     * @return \Zend\Barcode\Object
     */
    public function setWithBorder($value)
    {
        $this->withBorder = (bool) $value;
        return $this;
    }

    /**
     * Retrieve if border are draw or not
     * @return boolean
     */
    public function getWithBorder()
    {
        return $this->withBorder;
    }

    /**
     * Activate/deactivate drawing of the quiet zones
     * @param boolean $value
     * @return Zend\Barcode\AbstractObject
     */
    public function setWithQuietZones($value)
    {
        $this->withQuietZones = (bool) $value;
        return $this;
    }

    /**
     * Retrieve if quiet zones are draw or not
     * @return boolean
     */
    public function getWithQuietZones()
    {
        return $this->withQuietZones;
    }

    /**
     * Allow fast inversion of font/bars color and background color
     * @return \Zend\Barcode\Object
     */
    public function setReverseColor()
    {
        $tmp                    = $this->foreColor;
        $this->foreColor       = $this->backgroundColor;
        $this->backgroundColor = $tmp;
        return $this;
    }

    /**
     * Set orientation of barcode and text
     * @param float $value
     * @return \Zend\Barcode\Object
     * @throw \Zend\Barcode\Object\Exception
     */
    public function setOrientation($value)
    {
        $this->orientation = floatval($value) - floor(floatval($value) / 360) * 360;
        return $this;
    }

    /**
     * Retrieve orientation of barcode and text
     * @return float
     */
    public function getOrientation()
    {
        return $this->orientation;
    }

    /**
     * Set text to encode
     * @param string $value
     * @return \Zend\Barcode\Object
     */
    public function setText($value)
    {
        $this->text = trim($value);
        return $this;
    }

    /**
     * Retrieve text to encode
     * @return string
     */
    public function getText()
    {
        $text = $this->text;
        if ($this->withChecksum) {
            $text .= $this->getChecksum($this->text);
        }
        return $this->addLeadingZeros($text);
    }

    /**
     * Automatically add leading zeros if barcode length is fixed
     * @param string $text
     * @param boolean $withoutChecksum
     */
    protected function addLeadingZeros($text, $withoutChecksum = false)
    {
        if ($this->barcodeLength && $this->addLeadingZeros) {
            $omitChecksum = (int) ($this->withChecksum && $withoutChecksum);
            if (is_int($this->barcodeLength)) {
                $length = $this->barcodeLength - $omitChecksum;
                if (strlen($text) < $length) {
                    $text = str_repeat('0', $length - strlen($text)) . $text;
                }
            } else {
                if ($this->barcodeLength == 'even') {
                    $text = ((strlen($text) - $omitChecksum) % 2 ? '0' . $text : $text);
                }
            }
        }
        return $text;
    }

    /**
     * Retrieve text to encode
     * @return string
     */
    public function getRawText()
    {
        return $this->text;
    }

    /**
     * Retrieve text to display
     * @return string
     */
    public function getTextToDisplay()
    {
        if ($this->withChecksumInText) {
            return $this->getText();
        } else {
            return $this->addLeadingZeros($this->text, true);
        }
    }

    /**
     * Activate/deactivate drawing of text to encode
     * @param boolean $value
     * @return \Zend\Barcode\Object
     */
    public function setDrawText($value)
    {
        $this->drawText = (bool) $value;
        return $this;
    }

    /**
     * Retrieve if drawing of text to encode is enabled
     * @return boolean
     */
    public function getDrawText()
    {
        return $this->drawText;
    }

    /**
     * Activate/deactivate the adjustment of the position
     * of the characters to the position of the bars
     * @param boolean $value
     * @return \Zend\Barcode\Object
     * @throw \Zend\Barcode\Object\Exception
     */
    public function setStretchText($value)
    {
        $this->stretchText = (bool) $value;
        return $this;
    }

    /**
     * Retrieve if the adjustment of the position of the characters
     * to the position of the bars is enabled
     * @return boolean
     */
    public function getStretchText()
    {
        return $this->stretchText;
    }

    /**
     * Activate/deactivate the automatic generation
     * of the checksum character
     * added to the barcode text
     * @param boolean $value
     * @return \Zend\Barcode\Object
     */
    public function setWithChecksum($value)
    {
        if (!$this->mandatoryChecksum) {
            $this->withChecksum = (bool) $value;
        }
        return $this;
    }

    /**
     * Retrieve if the checksum character is automatically
     * added to the barcode text
     * @return boolean
     */
    public function getWithChecksum()
    {
        return $this->withChecksum;
    }

    /**
     * Activate/deactivate the automatic generation
     * of the checksum character
     * added to the barcode text
     * @param boolean $value
     * @return \Zend\Barcode\Object
     * @throw \Zend\Barcode\Object\Exception
     */
    public function setWithChecksumInText($value)
    {
        if (!$this->mandatoryChecksum) {
            $this->withChecksumInText = (bool) $value;
        }
        return $this;
    }

    /**
     * Retrieve if the checksum character is automatically
     * added to the barcode text
     * @return boolean
     */
    public function getWithChecksumInText()
    {
        return $this->withChecksumInText;
    }

    /**
     * Set the font:
     *  - if integer between 1 and 5, use gd built-in fonts
     *  - if string, $value is assumed to be the path to a TTF font
     * @param integer|string $value
     * @return \Zend\Barcode\Object
     * @throw \Zend\Barcode\Object\Exception
     */
    public function setFont($value)
    {
        if (is_int($value) && $value >= 1 && $value <= 5) {
            if (!extension_loaded('gd')) {
                throw new Exception\ExtensionNotLoadedException(
                    'GD extension is required to use numeric font'
                );
            }

            // Case of numeric font with GD
            $this->font = $value;

            // In this case font size is given by:
            $this->fontSize = imagefontheight($value);
        } elseif (is_string($value)) {
            $this->font = $value;
        } else {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid font "%s" provided to setFont()',
                $value
            ));
        }
        return $this;
    }

    /**
     * Retrieve the font
     * @return integer|string
     */
    public function getFont()
    {
        return $this->font;
    }

    /**
     * Set the size of the font in case of TTF
     * @param float $value
     * @return \Zend\Barcode\Object
     * @throw \Zend\Barcode\Object\Exception
     */
    public function setFontSize($value)
    {
        if (is_numeric($this->font)) {
            // Case of numeric font with GD
            return $this;
        }

        if (!is_numeric($value)) {
            throw new Exception\InvalidArgumentException(
                'Font size must be a numeric value'
            );
        }

        $this->fontSize = $value;
        return $this;
    }

    /**
     * Retrieve the size of the font in case of TTF
     * @return float
     */
    public function getFontSize()
    {
        return $this->fontSize;
    }

    /**
     * Quiet zone before first bar
     * and after the last bar
     * @return integer
     */
    public function getQuietZone()
    {
        if ($this->withQuietZones || $this->mandatoryQuietZones) {
            return 10 * $this->barThinWidth * $this->factor;
        } else {
            return 0;
        }
    }

    /**
     * Add an instruction in the array of instructions
     * @param array $instruction
     */
    protected function addInstruction(array $instruction)
    {
        $this->instructions[] = $instruction;
    }

    /**
     * Retrieve the set of drawing instructions
     * @return array
     */
    public function getInstructions()
    {
        return $this->instructions;
    }

    /**
     * Add a polygon drawing instruction in the set of instructions
     * @param array $points
     * @param integer $color
     * @param boolean $filled
     */
    protected function addPolygon(array $points, $color = null, $filled = true)
    {
        if ($color === null) {
            $color = $this->foreColor;
        }
        $this->addInstruction(array(
            'type'   => 'polygon',
            'points' => $points,
            'color'  => $color,
            'filled' => $filled,
        ));
    }

    /**
     * Add a text drawing instruction in the set of instructions
     * @param string $text
     * @param float $size
     * @param array $position
     * @param string $font
     * @param integer $color
     * @param string $alignment
     * @param float $orientation
     */
    protected function addText(
        $text,
        $size,
        $position,
        $font,
        $color,
        $alignment = 'center',
        $orientation = 0
    ) {
        if ($color === null) {
            $color = $this->foreColor;
        }
        $this->addInstruction(array(
            'type'        => 'text',
            'text'        => $text,
            'size'        => $size,
            'position'    => $position,
            'font'        => $font,
            'color'       => $color,
            'alignment'   => $alignment,
            'orientation' => $orientation,
        ));
    }

    /**
     * Checking of parameters after all settings
     * @return void
     */
    public function checkParams()
    {
        $this->checkText();
        $this->checkFontAndOrientation();
        $this->checkSpecificParams();
        return true;
    }

    /**
     * Check if a text is really provided to barcode
     * @return void
     * @throw \Zend\Barcode\Object\Exception
     */
    protected function checkText($value = null)
    {
        if ($value === null) {
            $value = $this->text;
        }
        if (!strlen($value)) {
            throw new Exception\RuntimeException(
                'A text must be provide to Barcode before drawing'
            );
        }
        $this->validateText($value);
    }

    /**
     * Check the ratio between the thick and the thin bar
     * @param integer $min
     * @param integer $max
     * @return void
     * @throw \Zend\Barcode\Object\Exception
     */
    protected function checkRatio($min = 2, $max = 3)
    {
        $ratio = $this->barThickWidth / $this->barThinWidth;
        if (!($ratio >= $min && $ratio <= $max)) {
            throw new Exception\OutOfRangeException(sprintf(
                'Ratio thick/thin bar must be between %0.1f and %0.1f (actual %0.3f)',
                $min,
                $max,
                $ratio
            ));
        }
    }

    /**
     * Drawing with an angle is just allow TTF font
     * @return void
     * @throw \Zend\Barcode\Object\Exception
     */
    protected function checkFontAndOrientation()
    {
        if (is_numeric($this->font) && $this->orientation != 0) {
            throw new Exception\RuntimeException(
                'Only drawing with TTF font allow orientation of the barcode.'
            );
        }
    }

    /**
     * Width of the result image
     * (before any rotation)
     * @return integer
     */
    protected function calculateWidth()
    {
        return (int) $this->withBorder
            + $this->calculateBarcodeWidth()
            + (int) $this->withBorder;
    }

    /**
     * Calculate the width of the barcode
     * @return integer
     */
    abstract protected function calculateBarcodeWidth();

    /**
     * Height of the result object
     * @return integer
     */
    protected function calculateHeight()
    {
        return (int) $this->withBorder * 2
            + $this->calculateBarcodeHeight()
            + (int) $this->withBorder * 2;
    }

    /**
     * Height of the barcode
     * @return integer
     */
    protected function calculateBarcodeHeight()
    {
        $textHeight = 0;
        $extraHeight = 0;
        if ($this->drawText) {
            $textHeight += $this->fontSize;
            $extraHeight = 2;
        }
        return ($this->barHeight + $textHeight) * $this->factor + $extraHeight;
    }

    /**
     * Get height of the result object
     * @return integer
     */
    public function getHeight($recalculate = false)
    {
        if ($this->height === null || $recalculate) {
            $this->height =
                abs($this->calculateHeight() * cos($this->orientation / 180 * pi()))
                + abs($this->calculateWidth() * sin($this->orientation / 180 * pi()));
        }
        return $this->height;
    }

    /**
     * Get width of the result object
     * @return integer
     */
    public function getWidth($recalculate = false)
    {
        if ($this->width === null || $recalculate) {
            $this->width =
                abs($this->calculateWidth() * cos($this->orientation / 180 * pi()))
                + abs($this->calculateHeight() * sin($this->orientation / 180 * pi()));
        }
        return $this->width;
    }

    /**
     * Calculate the offset from the left of the object
     * if an orientation is activated
     * @param boolean $recalculate
     * @return float
     */
    public function getOffsetLeft($recalculate = false)
    {
        if ($this->offsetLeft === null || $recalculate) {
            $this->offsetLeft = - min(array(
                0 * cos(
                        $this->orientation / 180 * pi()) - 0 * sin(
                        $this->orientation / 180 * pi()),
                0 * cos(
                        $this->orientation / 180 * pi()) - $this->calculateBarcodeHeight() * sin(
                        $this->orientation / 180 * pi()),
                $this->calculateBarcodeWidth() * cos(
                        $this->orientation / 180 * pi()) - $this->calculateBarcodeHeight() * sin(
                        $this->orientation / 180 * pi()),
                $this->calculateBarcodeWidth() * cos(
                        $this->orientation / 180 * pi()) - 0 * sin(
                        $this->orientation / 180 * pi()),
            ));
        }
        return $this->offsetLeft;
    }

    /**
     * Calculate the offset from the top of the object
     * if an orientation is activated
     * @param boolean $recalculate
     * @return float
     */
    public function getOffsetTop($recalculate = false)
    {
        if ($this->offsetTop === null || $recalculate) {
            $this->offsetTop = - min(array(
                0 * cos(
                        $this->orientation / 180 * pi()) + 0 * sin(
                        $this->orientation / 180 * pi()),
                $this->calculateBarcodeHeight() * cos(
                        $this->orientation / 180 * pi()) + 0 * sin(
                        $this->orientation / 180 * pi()),
                $this->calculateBarcodeHeight() * cos(
                        $this->orientation / 180 * pi()) + $this->calculateBarcodeWidth() * sin(
                        $this->orientation / 180 * pi()),
                0 * cos(
                        $this->orientation / 180 * pi()) + $this->calculateBarcodeWidth() * sin(
                        $this->orientation / 180 * pi()),
            ));
        }
        return $this->offsetTop;
    }

    /**
     * Apply rotation on a point in X/Y dimensions
     * @param float $x1     x-position before rotation
     * @param float $y1     y-position before rotation
     * @return array        Array of two elements corresponding to the new XY point
     */
    protected function rotate($x1, $y1)
    {
        $x2 = $x1 * cos($this->orientation / 180 * pi())
            - $y1 * sin($this->orientation / 180 * pi())
            + $this->getOffsetLeft();
        $y2 = $y1 * cos($this->orientation / 180 * pi())
            + $x1 * sin($this->orientation / 180 * pi())
            + $this->getOffsetTop();
        return array(intval($x2) , intval($y2));
    }

    /**
     * Complete drawing of the barcode
     * @return array Table of instructions
     */
    public function draw()
    {
        $this->checkParams();
        $this->drawBarcode();
        $this->drawBorder();
        $this->drawText();
        return $this->getInstructions();
    }

    /**
     * Draw the barcode
     * @return void
     */
    protected function drawBarcode()
    {
        $barcodeTable = $this->prepareBarcode();

        $this->preDrawBarcode();

        $xpos = (int) $this->withBorder;
        $ypos = (int) $this->withBorder;

        $point1 = $this->rotate(0, 0);
        $point2 = $this->rotate(0, $this->calculateHeight() - 1);
        $point3 = $this->rotate(
            $this->calculateWidth() - 1,
            $this->calculateHeight() - 1
        );
        $point4 = $this->rotate($this->calculateWidth() - 1, 0);

        $this->addPolygon(array(
            $point1,
            $point2,
            $point3,
            $point4
        ), $this->backgroundColor);

        $xpos     += $this->getQuietZone();
        $barLength = $this->barHeight * $this->factor;

        foreach ($barcodeTable as $bar) {
            $width = $bar[1] * $this->factor;
            if ($bar[0]) {
                $point1 = $this->rotate($xpos, $ypos + $bar[2] * $barLength);
                $point2 = $this->rotate($xpos, $ypos + $bar[3] * $barLength);
                $point3 = $this->rotate(
                    $xpos + $width - 1,
                    $ypos + $bar[3] * $barLength
                );
                $point4 = $this->rotate(
                    $xpos + $width - 1,
                    $ypos + $bar[2] * $barLength
                );
                $this->addPolygon(array(
                    $point1,
                    $point2,
                    $point3,
                    $point4,
                ));
            }
            $xpos += $width;
        }

        $this->postDrawBarcode();
    }

    /**
     * Partial function to draw border
     * @return void
     */
    protected function drawBorder()
    {
        if ($this->withBorder) {
            $point1 = $this->rotate(0, 0);
            $point2 = $this->rotate($this->calculateWidth() - 1, 0);
            $point3 = $this->rotate(
                $this->calculateWidth() - 1,
                $this->calculateHeight() - 1
            );
            $point4 = $this->rotate(0, $this->calculateHeight() - 1);
            $this->addPolygon(array(
                $point1,
                $point2,
                $point3,
                $point4,
                $point1,
            ), $this->foreColor, false);
        }
    }

    /**
     * Partial function to draw text
     * @return void
     */
    protected function drawText()
    {
        if ($this->drawText) {
            $text = $this->getTextToDisplay();
            if ($this->stretchText) {
                $textLength = strlen($text);
                $space      = ($this->calculateWidth() - 2 * $this->getQuietZone()) / $textLength;
                for ($i = 0; $i < $textLength; $i ++) {
                    $leftPosition = $this->getQuietZone() + $space * ($i + 0.5);
                    $this->addText(
                        $text{$i},
                        $this->fontSize * $this->factor,
                        $this->rotate(
                            $leftPosition,
                            (int) $this->withBorder * 2
                                + $this->factor * ($this->barHeight + $this->fontSize) + 1
                        ),
                        $this->font,
                        $this->foreColor,
                        'center',
                        - $this->orientation
                    );
                }
            } else {
                $this->addText(
                    $text,
                    $this->fontSize * $this->factor,
                    $this->rotate(
                        $this->calculateWidth() / 2,
                        (int) $this->withBorder * 2
                            + $this->factor * ($this->barHeight + $this->fontSize) + 1
                    ),
                    $this->font,
                    $this->foreColor,
                    'center',
                    - $this->orientation
                );
            }
        }
    }

    /**
     * Check for invalid characters
     * @param   string $value    Text to be ckecked
     * @return void
     */
    public function validateText($value)
    {
        $this->validateSpecificText($value);
    }

    /**
     * Standard validation for most of barcode objects
     * @param string $value
     * @param array  $options
     */
    protected function validateSpecificText($value, $options = array())
    {
        $validatorName = (isset($options['validator'])) ? $options['validator'] : $this->getType();

        $validator = new BarcodeValidator(array(
            'adapter'  => $validatorName,
            'usechecksum' => false,
        ));

        $checksumCharacter = '';
        $withChecksum = false;
        if ($this->mandatoryChecksum) {
            $checksumCharacter = $this->substituteChecksumCharacter;
            $withChecksum = true;
        }

        $value = $this->addLeadingZeros($value, $withChecksum) . $checksumCharacter;

        if (!$validator->isValid($value)) {
            $message = implode("\n", $validator->getMessages());
            throw new Exception\BarcodeValidationException($message);
        }
    }

    /**
     * Each child must prepare the barcode and return
     * a table like array(
     *     0 => array(
     *         0 => int (visible(black) or not(white))
     *         1 => int (width of the bar)
     *         2 => float (0->1 position from the top of the beginning of the bar in %)
     *         3 => float (0->1 position from the top of the end of the bar in %)
     *     ),
     *     1 => ...
     * )
     *
     * @return array
     */
    abstract protected function prepareBarcode();

    /**
     * Checking of parameters after all settings
     *
     * @return void
     */
    abstract protected function checkSpecificParams();

    /**
     * Allow each child to draw something else
     *
     * @return void
     */
    protected function preDrawBarcode()
    {
    }

    /**
     * Allow each child to draw something else
     * (ex: bearer bars in interleaved 2 of 5 code)
     *
     * @return void
     */
    protected function postDrawBarcode()
    {
    }
}
