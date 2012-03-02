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
 * @package    Zend_Form
 * @subpackage Decorator
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Form\Decorator\Captcha;

use Zend\Form\Decorator\AbstractDecorator;

/**
 * Word-based captcha decorator
 *
 * Adds hidden field for ID and text input field for captcha text
 *
 * @uses       \Zend\Form\Decorator\AbstractDecorator
 * @category   Zend
 * @package    Zend_Form
 * @subpackage Element
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Word extends AbstractDecorator
{
    /**
     * Render captcha
     *
     * @param  string $content
     * @return string
     */
    public function render($content)
    {
        $element = $this->getElement();
        $view    = $element->getView();
        if (null === $view) {
            return $content;
        }

        $name = $element->getFullyQualifiedName();

        $hiddenName = $name . '[id]';
        $textName   = $name . '[input]';

        $label = $element->getDecorator("Label");
        if($label) {
            $label->setOption("id", $element->getId()."-input");
        }

        $placement = $this->getPlacement();
        $separator = $this->getSeparator();

        $hidden = $view->formHidden($hiddenName, $element->getValue(), $element->getAttribs());
        $text   = $view->formText($textName, '', $element->getAttribs());
        switch ($placement) {
            case 'PREPEND':
                $content = $hidden . $separator . $text . $separator . $content;
                break;
            case 'APPEND':
            default:
                $content = $content . $separator . $hidden . $separator . $text;
        }
        return $content;
    }
}
