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
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\View\Helper;

/**
 * @uses       \Zend\View\Helper\HtmlObject
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class HtmlPage extends HtmlElement
{
    /**
     * Default file type for html
     *
     */
    const TYPE = 'text/html';

    /**
     * Object classid
     *
     */
    const ATTRIB_CLASSID  = 'clsid:25336920-03F9-11CF-8FD0-00AA00686F13';

    /**
     * Default attributes
     *
     * @var array
     */
    protected $_attribs = array('classid' => self::ATTRIB_CLASSID);

    /**
     * Output a html object tag
     *
     * @param string $data The html url
     * @param array  $attribs Attribs for the object tag
     * @param array  $params Params for in the object tag
     * @param string $content Alternative content
     * @return string
     */
    public function __invoke($data, array $attribs = array(), array $params = array(), $content = null)
    {
        // Attrs
        $attribs = array_merge($this->_attribs, $attribs);

        // Params
        $params = array_merge(array('data' => $data), $params);

        $htmlObject = $this->getView()->plugin('htmlObject');
        return $htmlObject($data, self::TYPE, $attribs, $params, $content);
    }
}
