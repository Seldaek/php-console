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
 * @package    Zend_Json
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Json\Server\Request;

use Zend\Json\Server\Request as JsonRequest;

/**
 * @uses       \Zend\Json\Server\Request\Request
 * @category   Zend
 * @package    Zend_Json
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Http extends JsonRequest
{
    /**
     * Raw JSON pulled from POST body
     * @var string
     */
    protected $_rawJson;

    /**
     * Constructor
     *
     * Pull JSON request from raw POST body and use to populate request.
     *
     * @return void
     */
    public function __construct()
    {
        $json = file_get_contents('php://input');
        $this->_rawJson = $json;
        if (!empty($json)) {
            $this->loadJson($json);
        }
    }

    /**
     * Get JSON from raw POST body
     *
     * @return string
     */
    public function getRawJson()
    {
        return $this->_rawJson;
    }
}
