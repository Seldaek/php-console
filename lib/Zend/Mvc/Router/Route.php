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
 * @package    Zend_Mvc_Router
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Mvc\Router;

use Zend\Stdlib\RequestDescription as Request;

/**
 * Route interface.
 * 
 * @package    Zend_Mvc_Router
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
interface Route
{
    /**
     * Priority used for route stacks.
     * 
     * @var integer
     * public $priority;
     */
    
    /**
     * Create a new route with given options.
     * 
     * @param  array|Traversable $options
     * @return void
     */
    public static function factory($options = array());
    
    /**
     * Match a given request.
     * 
     * @param  Request $request
     * @return RouteMatch
     */
    public function match(Request $request);
    
    /**
     * Assemble the route.
     * 
     * @param  array $params
     * @param  array $options
     * @return mixed
     */
    public function assemble(array $params = array(), array $options = array());
}
