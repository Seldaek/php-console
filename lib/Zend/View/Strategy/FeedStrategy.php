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
 * @subpackage Strategy
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Zend\View\Strategy;

use Zend\EventManager\EventCollection,
    Zend\EventManager\ListenerAggregate,
    Zend\Feed\Writer\Feed,
    Zend\Http\Request as HttpRequest,
    Zend\Http\Response as HttpResponse,
    Zend\View\Model,
    Zend\View\Renderer\FeedRenderer,
    Zend\View\ViewEvent;

/**
 * @category   Zend
 * @package    Zend_View
 * @subpackage Strategy
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class FeedStrategy implements ListenerAggregate
{
    /**
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $listeners = array();

    /**
     * @var FeedRenderer
     */
    protected $renderer;

    /**
     * Constructor
     * 
     * @param  FeedRenderer $renderer 
     * @return void
     */
    public function __construct(FeedRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Attach the aggregate to the specified event manager
     * 
     * @param  EventCollection $events 
     * @param  int $priority 
     * @return void
     */
    public function attach(EventCollection $events, $priority = 1)
    {
        $this->listeners[] = $events->attach('renderer', array($this, 'selectRenderer'), $priority);
        $this->listeners[] = $events->attach('response', array($this, 'injectResponse'), $priority);
    }

    /**
     * Detach aggregate listeners from the specified event manager
     * 
     * @param  EventCollection $events 
     * @return void
     */
    public function detach(EventCollection $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * Detect if we should use the FeedRenderer based on model type and/or 
     * Accept header
     * 
     * @param  ViewEvent $e 
     * @return null|FeedRenderer
     */
    public function selectRenderer(ViewEvent $e)
    {
        $model = $e->getModel();

        if ($model instanceof Model\FeedModel) {
            // FeedModel found
            return $this->renderer;
        }

        $request = $e->getRequest();
        if (!$request instanceof HttpRequest) {
            // Not an HTTP request; cannot autodetermine
            return;
        }

        $headers = $request->headers();
        if ($headers->has('accept')) {
            $accept  = $headers->get('accept');
            foreach ($accept->getPrioritized() as $mediaType) {
                if (0 === strpos($mediaType, 'application/rss+xml')) {
                    // application/rss+xml Accept header found
                    $this->renderer->setFeedType('rss');
                    return $this->renderer;
                }
                if (0 === strpos($mediaType, 'application/atom+xml')) {
                    // application/atom+xml Accept header found
                    $this->renderer->setFeedType('atom');
                    return $this->renderer;
                }
            }
        }

        // Not matched!
        return;
    }

    /**
     * Inject the response with the feed payload and appropriate Content-Type header
     * 
     * @param  ViewEvent $e 
     * @return void
     */
    public function injectResponse(ViewEvent $e)
    {
        $renderer = $e->getRenderer();
        if ($renderer !== $this->renderer) {
            // Discovered renderer is not ours; do nothing
            return;
        }

        $result   = $e->getResult();
        if (!is_string($result) && !$result instanceof Feed) {
            // We don't have a string, and thus, no feed
            return;
        }

        // If the result is a feed, export it
        if ($result instanceof Feed) {
            $result = $result->export($renderer->getFeedType());
        }
        
        // Get the content-type header based on feed type
        $feedType = $renderer->getFeedType();
        $feedType = ('rss' == $feedType) 
                  ? 'application/rss+xml'
                  : 'application/atom+xml';

        // Populate response
        $response = $e->getResponse();
        $response->setContent($result);
        $headers = $response->headers();
        $headers->addHeaderLine('content-type', $feedType);
    }
}
