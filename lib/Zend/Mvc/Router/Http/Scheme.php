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
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Mvc\Router\Http;

use Traversable,
    Zend\Stdlib\IteratorToArray,
    Zend\Stdlib\RequestDescription as Request,
    Zend\Mvc\Router\Exception;

/**
 * Scheme route.
 *
 * @package    Zend_Mvc_Router
 * @subpackage Http
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @see        http://manuals.rubyonrails.com/read/chapter/65
 */
class Scheme implements Route
{
    /**
     * Scheme to match.
     *
     * @var array
     */
    protected $scheme;
    
    /**
     * Default values.
     *
     * @var array
     */
    protected $defaults;

    /**
     * Create a new scheme route.
     * 
     * @param  string $scheme
     * @param  array  $defaults 
     * @return void
     */
    public function __construct($scheme, array $defaults = array())
    {
        $this->scheme   = $scheme;
        $this->defaults = $defaults;
    }
    
    /**
     * factory(): defined by Route interface.
     *
     * @see    Route::factory()
     * @param  array|Traversable $options
     * @return void
     */
    public static function factory($options = array())
    {
        if ($options instanceof Traversable) {
            $options = IteratorToArray::convert($options);
        } elseif (!is_array($options)) {
            throw new Exception\InvalidArgumentException(__METHOD__ . ' expects an array or Traversable set of options');
        }

        if (!isset($options['scheme'])) {
            throw new Exception\InvalidArgumentException('Missing "scheme" in options array');
        }
        
        if (!isset($options['defaults'])) {
            $options['defaults'] = array();
        }

        return new static($options['scheme'], $options['defaults']);
    }

    /**
     * match(): defined by Route interface.
     *
     * @see    Route::match()
     * @param  Request $request
     * @return RouteMatch
     */
    public function match(Request $request)
    {
        if (!method_exists($request, 'uri')) {
            return null;
        }

        $uri    = $request->uri();
        $scheme = $uri->getScheme();

        if ($scheme !== $this->scheme) {
            return null;
        }
        
        return new RouteMatch($this->defaults);
    }

    /**
     * assemble(): Defined by Route interface.
     *
     * @see    Route::assemble()
     * @param  array $params
     * @param  array $options
     * @return mixed
     */
    public function assemble(array $params = array(), array $options = array())
    {
        if (isset($options['uri'])) {
            $options['uri']->setScheme($this->scheme);
        }
        
        // A scheme does not contribute to the path, thus nothing is returned.
        return '';
    }
    
    /**
     * getAssembledParams(): defined by Route interface.
     * 
     * @see    Route::getAssembledParams
     * @return array
     */
    public function getAssembledParams()
    {
        return array();
    }
}
