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
 * @subpackage Http
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Mvc\Router\Http;

use Zend\Mvc\Router\RouteMatch as BaseRouteMatch;

/**
 * Part route match.
 *
 * @package    Zend_Mvc_Router
 * @subpackage Http
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class RouteMatch extends BaseRouteMatch
{
    /**
     * Length of the matched path.
     * 
     * @var integer
     */
    protected $length;
    
    /**
     * Create a part RouteMatch with given parameters and length.
     * 
     * @param  array   $params
     * @param  integer $length
     * @return void
     */
    public function __construct(array $params, $length = 0)
    {
        parent::__construct($params);
        
        $this->length = $length;
    }
    
    /**
     * setMatchedRouteName(): defined by BaseRouteMatch.
     * 
     * @see    BaseRouteMatch::setMatchedRouteName()
     * @param  string $name
     * @return self
     */
    public function setMatchedRouteName($name)
    {
        if ($this->matchedRouteName === null) {
            $this->matchedRouteName = $name;
        } else {
            $this->matchedRouteName = $name . '/' . $this->matchedRouteName;
        }
        
        return $this;
    }
    
    /**
     * Merge parameters from another match.
     * 
     * @param  self $match
     * @return self
     */
    public function merge(self $match)
    {
        $this->params  = array_merge($this->params, $match->getParams());
        $this->length += $match->getLength();
        
        $this->matchedRouteName = $match->getMatchedRouteName();
        
        return $this;
    }

    /**
     * Get the matched path length.
     * 
     * @return integer
     */
    public function getLength()
    {
        return $this->length;
    }
}
