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

use Zend\Barcode\Renderer\Exception,
    Zend\Pdf\Color,
    Zend\Pdf\Font,
    Zend\Pdf\Page,
    Zend\Pdf\PdfDocument;

/**
 * Class for rendering the barcode in PDF resource
 *
 * @category   Zend
 * @package    Zend_Barcode
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Pdf extends AbstractRenderer
{
    /**
     * PDF resource
     * @var PdfDocument
     */
    protected $resource = null;

    /**
     * Page number in PDF resource
     * @var integer
     */
    protected $page = 0;

    /**
     * Module size rendering
     * @var float
     */
    protected $moduleSize = 0.5;

    /**
     * Set an image resource to draw the barcode inside
     * @param resource $value
     * @return \Zend\Barcode\Renderer
     * @throw  Exception
     */
    public function setResource($pdf, $page = 0)
    {
        if (!$pdf instanceof PdfDocument) {
            throw new Exception\InvalidArgumentException(
                'Invalid Zend\Pdf\PdfDocument resource provided to setResource()'
            );
        }

        $this->resource = $pdf;
        $this->page     = intval($page);

        if (!count($this->resource->pages)) {
            $this->page = 0;
            $this->resource->pages[] = new Page(
                Page::SIZE_A4
            );
        }
        return $this;
    }

    /**
     * Check renderer parameters
     *
     * @return void
     */
    protected function checkSpecificParams()
    {
    }

    /**
     * Draw the barcode in the PDF, send headers and the PDF
     * @return mixed
     */
    public function render()
    {
        $this->draw();
        header("Content-Type: application/pdf");
        echo $this->resource->render();
    }

    /**
     * Initialize the PDF resource
     * @return void
     */
    protected function initRenderer()
    {
        if ($this->resource === null) {
            $this->resource = new PdfDocument();
            $this->resource->pages[] = new Page(
                Page::SIZE_A4
            );
        }

        $pdfPage = $this->resource->pages[$this->page];
        $this->adjustPosition($pdfPage->getHeight(), $pdfPage->getWidth());
    }

    /**
     * Draw a polygon in the rendering resource
     * @param array $points
     * @param integer $color
     * @param boolean $filled
     */
    protected function drawPolygon($points, $color, $filled = true)
    {
        $page = $this->resource->pages[$this->page];
        foreach ($points as $point) {
            $x[] = $point[0] * $this->moduleSize + $this->leftOffset;
            $y[] = $page->getHeight() - $point[1] * $this->moduleSize - $this->topOffset;
        }
        if (count($y) == 4) {
            if ($x[0] != $x[3] && $y[0] == $y[3]) {
                $y[0] -= ($this->moduleSize / 2);
                $y[3] -= ($this->moduleSize / 2);
            }
            if ($x[1] != $x[2] && $y[1] == $y[2]) {
                $y[1] += ($this->moduleSize / 2);
                $y[2] += ($this->moduleSize / 2);
            }
        }

        $color = new Color\Rgb(
            (($color & 0xFF0000) >> 16) / 255.0,
            (($color & 0x00FF00) >> 8) / 255.0,
            ($color & 0x0000FF) / 255.0
        );

        $page->setLineColor($color);
        $page->setFillColor($color);
        $page->setLineWidth($this->moduleSize);

        $fillType = ($filled)
                  ? Page::SHAPE_DRAW_FILL_AND_STROKE
                  : Page::SHAPE_DRAW_STROKE;

        $page->drawPolygon($x, $y, $fillType);
    }

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
    protected function drawText(
        $text,
        $size,
        $position,
        $font,
        $color,
        $alignment = 'center',
        $orientation = 0
    ) {
        $page  = $this->resource->pages[$this->page];
        $color = new Color\Rgb(
            (($color & 0xFF0000) >> 16) / 255.0,
            (($color & 0x00FF00) >> 8) / 255.0,
            ($color & 0x0000FF) / 255.0
        );

        $page->setLineColor($color);
        $page->setFillColor($color);
        $page->setFont(Font::fontWithPath($font), $size * $this->moduleSize * 1.2);

        $width = $this->widthForStringUsingFontSize(
            $text,
            Font::fontWithPath($font),
            $size * $this->moduleSize
        );

        $angle = pi() * $orientation / 180;
        $left = $position[0] * $this->moduleSize + $this->leftOffset;
        $top  = $page->getHeight() - $position[1] * $this->moduleSize - $this->topOffset;

        switch ($alignment) {
            case 'center':
                $left -= ($width / 2) * cos($angle);
                $top  -= ($width / 2) * sin($angle);
                break;
            case 'right':
                $left -= $width;
                break;
        }
        $page->rotate($left, $top, $angle);
        $page->drawText($text, $left, $top);
        $page->rotate($left, $top, - $angle);
    }

    /**
     * Calculate the width of a string:
     * in case of using alignment parameter in drawText
     * @param string $text
     * @param Font $font
     * @param float $fontSize
     * @return float
     */
    public function widthForStringUsingFontSize($text, $font, $fontSize)
    {
        $drawingString = iconv('UTF-8', 'UTF-16BE//IGNORE', $text);
        $characters    = array();
        for ($i = 0; $i < strlen($drawingString); $i ++) {
            $characters[] = (ord($drawingString[$i ++]) << 8) | ord($drawingString[$i]);
        }
        $glyphs = $font->glyphNumbersForCharacters($characters);
        $widths = $font->widthsForGlyphs($glyphs);
        $stringWidth = (array_sum($widths) / $font->getUnitsPerEm()) * $fontSize;
        return $stringWidth;
    }
}
