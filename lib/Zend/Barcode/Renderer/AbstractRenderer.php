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
 * @subpackage Renderer
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Barcode\Renderer;

use Traversable,
    Zend\Barcode\Barcode,
    Zend\Barcode\Exception as BarcodeException,
    Zend\Barcode\Object,
    Zend\Barcode\Renderer,
    Zend\Stdlib\IteratorToArray;

/**
 * Class for rendering the barcode
 *
 * @category   Zend
 * @package    Zend_Barcode
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class AbstractRenderer implements Renderer
{
    /**
     * Namespace of the renderer for autoloading
     * @var string
     */
    protected $rendererNamespace = 'Zend\Barcode\Renderer';

    /**
     * Renderer type
     * @var string
     */
    protected $type = null;

    /**
     * Activate/Deactivate the automatic rendering of exception
     * @var boolean
     */
    protected $automaticRenderError = false;

    /**
     * Offset of the barcode from the top of the rendering resource
     * @var integer
     */
    protected $topOffset = 0;

    /**
     * Offset of the barcode from the left of the rendering resource
     * @var integer
     */
    protected $leftOffset = 0;

    /**
     * Horizontal position of the barcode in the rendering resource
     * @var integer
     */
    protected $horizontalPosition = 'left';

    /**
     * Vertical position of the barcode in the rendering resource
     * @var integer
     */
    protected $verticalPosition = 'top';

    /**
     * Module size rendering
     * @var float
     */
    protected $moduleSize = 1;

    /**
     * Barcode object
     * @var Object
     */
    protected $barcode;

    /**
     * Drawing resource
     */
    protected $resource;

    /**
     * Constructor
     * @param array|Traversable $options
     * @return void
     */
    public function __construct($options = null)
    {
        if ($options instanceof Traversable) {
            $options = IteratorToArray::convert($options);
        }
        if (is_array($options)) {
            $this->setOptions($options);
        }
        $this->type = strtolower(substr(
            get_class($this),
            strlen($this->rendererNamespace) + 1
        ));
    }

    /**
     * Set renderer state from options array
     * @param  array $options
     * @return AbstractRenderer
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
     * Set renderer namespace for autoloading
     *
     * @param string $namespace
     * @return AbstractRenderer
     */
    public function setRendererNamespace($namespace)
    {
        $this->rendererNamespace = $namespace;
        return $this;
    }

    /**
     * Retrieve renderer namespace
     *
     * @return string
     */
    public function getRendererNamespace()
    {
        return $this->rendererNamespace;
    }

    /**
     * Retrieve renderer type
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Manually adjust top position
     * @param  integer $value
     * @return AbstractRenderer
     * @throws Exception\OutOfRangeException
     */
    public function setTopOffset($value)
    {
        if (!is_numeric($value) || intval($value) < 0) {
            throw new Exception\OutOfRangeException(
                'Vertical position must be greater than or equals 0'
            );
        }
        $this->topOffset = intval($value);
        return $this;
    }

    /**
     * Retrieve vertical adjustment
     * @return integer
     */
    public function getTopOffset()
    {
        return $this->topOffset;
    }

    /**
     * Manually adjust left position
     * @param  integer $value
     * @return AbstractRenderer
     * @throws Exception\OutOfRangeException
     */
    public function setLeftOffset($value)
    {
        if (!is_numeric($value) || intval($value) < 0) {
            throw new Exception\OutOfRangeException(
                'Horizontal position must be greater than or equals 0'
            );
        }
        $this->leftOffset = intval($value);
        return $this;
    }

    /**
     * Retrieve vertical adjustment
     * @return integer
     */
    public function getLeftOffset()
    {
        return $this->leftOffset;
    }

    /**
     * Activate/Deactivate the automatic rendering of exception
     * @param boolean $value
     */
    public function setAutomaticRenderError($value)
    {
        $this->automaticRenderError = (bool) $value;
        return $this;
    }

    /**
     * Horizontal position of the barcode in the rendering resource
     * @param  string $value
     * @return AbstractRenderer
     * @throws Exception\UnexpectedValueException
     */
    public function setHorizontalPosition($value)
    {
        if (!in_array($value, array('left' , 'center' , 'right'))) {
            throw new Exception\UnexpectedValueException(
                "Invalid barcode position provided must be 'left', 'center' or 'right'"
            );
        }
        $this->horizontalPosition = $value;
        return $this;
    }

    /**
     * Horizontal position of the barcode in the rendering resource
     * @return string
     */
    public function getHorizontalPosition()
    {
        return $this->horizontalPosition;
    }

    /**
     * Vertical position of the barcode in the rendering resource
     * @param  string $value
     * @return AbstractRenderer
     * @throws Exception\UnexpectedValueException
     */
    public function setVerticalPosition($value)
    {
        if (!in_array($value, array('top' , 'middle' , 'bottom'))) {
            throw new Exception\UnexpectedValueException(
                "Invalid barcode position provided must be 'top', 'middle' or 'bottom'"
            );
        }
        $this->verticalPosition = $value;
        return $this;
    }

    /**
     * Vertical position of the barcode in the rendering resource
     * @return string
     */
    public function getVerticalPosition()
    {
        return $this->verticalPosition;
    }

    /**
     * Set the size of a module
     * @param float $value
     * @return AbstractRenderer
     * @throws Exception\OutOfRangeException
     */
    public function setModuleSize($value)
    {
        if (!is_numeric($value) || floatval($value) <= 0) {
            throw new Exception\OutOfRangeException(
                'Float size must be greater than 0'
            );
        }
        $this->moduleSize = floatval($value);
        return $this;
    }


    /**
     * Set the size of a module
     * @return float
     */
    public function getModuleSize()
    {
        return $this->moduleSize;
    }

    /**
     * Retrieve the automatic rendering of exception
     * @return boolean
     */
    public function getAutomaticRenderError()
    {
        return $this->automaticRenderError;
    }

    /**
     * Set the barcode object
     * @param  Object $barcode
     * @return AbstractRenderer
     */
    public function setBarcode($barcode)
    {
        if (!$barcode instanceof Object) {
            throw new Exception\InvalidArgumentException(
                'Invalid barcode object provided to setBarcode()'
            );
        }
        $this->barcode = $barcode;
        return $this;
    }

    /**
     * Retrieve the barcode object
     * @return Object
     */
    public function getBarcode()
    {
        return $this->barcode;
    }

    /**
     * Checking of parameters after all settings
     * @return boolean
     */
    public function checkParams()
    {
        $this->checkBarcodeObject();
        $this->checkSpecificParams();
        return true;
    }

    /**
     * Check if a barcode object is correctly provided
     * @return void
     * @throws Exception\RuntimeException
     */
    protected function checkBarcodeObject()
    {
        if ($this->barcode === null) {
            throw new Exception\RuntimeException(
                'No barcode object provided'
            );
        }
    }

    /**
     * Calculate the left and top offset of the barcode in the
     * rendering support
     *
     * @param  float $supportHeight
     * @param  float $supportWidth
     * @return void
     */
    protected function adjustPosition($supportHeight, $supportWidth)
    {
        $barcodeHeight = $this->barcode->getHeight(true) * $this->moduleSize;
        if ($barcodeHeight != $supportHeight && $this->topOffset == 0) {
            switch ($this->verticalPosition) {
                case 'middle':
                    $this->topOffset = floor(
                            ($supportHeight - $barcodeHeight) / 2);
                    break;
                case 'bottom':
                    $this->topOffset = $supportHeight - $barcodeHeight;
                    break;
                case 'top':
                default:
                    $this->topOffset = 0;
                    break;
            }
        }
        $barcodeWidth = $this->barcode->getWidth(true) * $this->moduleSize;
        if ($barcodeWidth != $supportWidth && $this->leftOffset == 0) {
            switch ($this->horizontalPosition) {
                case 'center':
                    $this->leftOffset = floor(
                            ($supportWidth - $barcodeWidth) / 2);
                    break;
                case 'right':
                    $this->leftOffset = $supportWidth - $barcodeWidth;
                    break;
                case 'left':
                default:
                    $this->leftOffset = 0;
                    break;
            }
        }
    }

    /**
     * Draw the barcode in the rendering resource
     * @return mixed
     */
    public function draw()
    {
        try {
            $this->checkParams();
            $this->initRenderer();
            $this->drawInstructionList();
        } catch (BarcodeException $e) {
            if ($this->automaticRenderError && !($e instanceof BarcodeException\RendererCreationException)) {
                $barcode = Barcode::makeBarcode(
                    'error',
                    array('text' => $e->getMessage())
                );
                $this->setBarcode($barcode);
                $this->resource = null;
                $this->initRenderer();
                $this->drawInstructionList();
            } else {
                throw $e;
            }
        }
        return $this->resource;
    }

    /**
     * Sub process to draw the barcode instructions
     * Needed by the automatic error rendering
     */
    private function drawInstructionList()
    {
        $instructionList = $this->barcode->draw();
        foreach ($instructionList as $instruction) {
            switch ($instruction['type']) {
                case 'polygon':
                    $this->drawPolygon(
                        $instruction['points'],
                        $instruction['color'],
                        $instruction['filled']
                    );
                    break;
                case 'text': //$text, $size, $position, $font, $color, $alignment = 'center', $orientation = 0)
                    $this->drawText(
                        $instruction['text'],
                        $instruction['size'],
                        $instruction['position'],
                        $instruction['font'],
                        $instruction['color'],
                        $instruction['alignment'],
                        $instruction['orientation']
                    );
                    break;
                default:
                    throw new Exception\UnexpectedValueException(
                        'Unkown drawing command'
                    );
            }
        }
    }

    /**
     * Checking of parameters after all settings
     * @return void
     */
    abstract protected function checkSpecificParams();

    /**
     * Initialize the rendering resource
     * @return void
     */
    abstract protected function initRenderer();

    /**
     * Draw a polygon in the rendering resource
     * @param array $points
     * @param integer $color
     * @param boolean $filled
     */
    abstract protected function drawPolygon($points, $color, $filled = true);

    /**
     * Draw a polygon in the rendering resource
     * @param string $text
     * @param float $size
     * @param array $position
     * @param string $font
     * @param integer $color
     * @param string $alignment
     * @param float $orientation
     */
    abstract protected function drawText(
        $text,
        $size,
        $position,
        $font,
        $color,
        $alignment = 'center',
        $orientation = 0
    );
}
