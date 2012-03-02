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
namespace Zend\View\Helper\Navigation;

use DOMDocument,
    RecursiveIteratorIterator,
    Zend\Navigation\Page\AbstractPage,
    Zend\Navigation\Container,
    Zend\Uri,
    Zend\View,
    Zend\View\Exception;

/**
 * Helper for printing sitemaps
 *
 * @link http://www.sitemaps.org/protocol.php
 *
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Sitemap extends AbstractHelper
{
    /**
     * Namespace for the <urlset> tag
     *
     * @var string
     */
    const SITEMAP_NS = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    /**
     * Schema URL
     *
     * @var string
     */
    const SITEMAP_XSD = 'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd';

    /**
     * Whether XML output should be formatted
     *
     * @var bool
     */
    protected $formatOutput = false;

    /**
     * Whether the XML declaration should be included in XML output
     *
     * @var bool
     */
    protected $useXmlDeclaration = true;

    /**
     * Whether sitemap should be validated using Zend\Validate\Sitemap\*
     *
     * @var bool
     */
    protected $useSitemapValidators = true;

    /**
     * Whether sitemap should be schema validated when generated
     *
     * @var bool
     */
    protected $useSchemaValidation = false;

    /**
     * Server url
     *
     * @var string
     */
    protected $serverUrl;

    /**
     * View helper entry point:
     * Retrieves helper and optionally sets container to operate on
     *
     * @param  Container $container  [optional] container to operate on
     * @return Sitemap   fluent interface, returns self
     */
    public function __invoke(Container $container = null)
    {
        if (null !== $container) {
            $this->setContainer($container);
        }

        return $this;
    }

    // Accessors:

    /**
     * Sets whether XML output should be formatted
     *
     * @param  bool $formatOutput [optional] whether output should be formatted. Default is true.
     * @return Sitemap  fluent interface, returns self
     */
    public function setFormatOutput($formatOutput = true)
    {
        $this->formatOutput = (bool) $formatOutput;
        return $this;
    }

    /**
     * Returns whether XML output should be formatted
     *
     * @return bool  whether XML output should be formatted
     */
    public function getFormatOutput()
    {
        return $this->formatOutput;
    }

    /**
     * Sets whether the XML declaration should be used in output
     *
     * @param  bool $useXmlDecl whether XML delcaration should be rendered
     * @returnSitemap  fluent interface, returns self
     */
    public function setUseXmlDeclaration($useXmlDecl)
    {
        $this->useXmlDeclaration = (bool) $useXmlDecl;
        return $this;
    }

    /**
     * Returns whether the XML declaration should be used in output
     *
     * @return bool  whether the XML declaration should be used in output
     */
    public function getUseXmlDeclaration()
    {
        return $this->useXmlDeclaration;
    }

    /**
     * Sets whether sitemap should be validated using Zend\Validate\Sitemap_*
     *
     * @param  bool $useSitemapValidators whether sitemap validators should be used
     * @returnSitemap  fluent interface, returns self
     */
    public function setUseSitemapValidators($useSitemapValidators)
    {
        $this->useSitemapValidators = (bool) $useSitemapValidators;
        return $this;
    }

    /**
     * Returns whether sitemap should be validated using Zend\Validate\Sitemap_*
     *
     * @return bool  whether sitemap should be validated using validators
     */
    public function getUseSitemapValidators()
    {
        return $this->useSitemapValidators;
    }

    /**
     * Sets whether sitemap should be schema validated when generated
     *
     * @param  bool $schemaValidation whether sitemap should validated using XSD Schema
     * @returnSitemap  fluent interface, returns self
     */
    public function setUseSchemaValidation($schemaValidation)
    {
        $this->useSchemaValidation = (bool) $schemaValidation;
        return $this;
    }

    /**
     * Returns true if sitemap should be schema validated when generated
     *
     * @return bool
     */
    public function getUseSchemaValidation()
    {
        return $this->useSchemaValidation;
    }

    /**
     * Sets server url (scheme and host-related stuff without request URI)
     *
     * E.g. http://www.example.com
     *
     * @param  string $serverUrl server URL to set (only scheme and host)
     * @return Sitemap fluent interface, returns self
     * @throws Exception\InvalidArgumentException if invalid server URL
     */
    public function setServerUrl($serverUrl)
    {
        $uri = Uri\UriFactory::factory($serverUrl);
        $uri->setFragment('');
        $uri->setPath('');
        $uri->setQuery('');

        if ($uri->isValid()) {
            $this->serverUrl = $uri->toString();
        } else {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid server URL: "%s"',
                $serverUrl
            ));
        }

        return $this;
    }

    /**
     * Returns server URL
     *
     * @return string  server URL
     */
    public function getServerUrl()
    {
        if (!isset($this->serverUrl)) {
            $serverUrlHelper  = $this->getView()->plugin('serverUrl');
            $this->serverUrl = $serverUrlHelper();
        }

        return $this->serverUrl;
    }

    // Helper methods:

    /**
     * Escapes string for XML usage
     *
     * @param  string $string  string to escape
     * @return string          escaped string
     */
    protected function xmlEscape($string)
    {
        $enc = 'UTF-8';
        if ($this->view instanceof View\Renderer
            && method_exists($this->view, 'getEncoding')
        ) {
            $enc = $this->view->getEncoding();
        }

        return htmlspecialchars($string, ENT_QUOTES, $enc, false);
    }

    // Public methods:

    /**
     * Returns an escaped absolute URL for the given page
     *
     * @param  AbstractPage $page  page to get URL from
     * @return string
     */
    public function url(AbstractPage $page)
    {
        $href = $page->getHref();

        if (!isset($href{0})) {
            // no href
            return '';
        } elseif ($href{0} == '/') {
            // href is relative to root; use serverUrl helper
            $url = $this->getServerUrl() . $href;
        } elseif (preg_match('/^[a-z]+:/im', (string) $href)) {
            // scheme is given in href; assume absolute URL already
            $url = (string) $href;
        } else {
            // href is relative to current document; use url helpers
            $basePathHelper = $this->getView()->plugin('basepath');
            $curDoc         = $basePathHelper();
            $curDoc         = ('/' == $curDoc) ? '' : trim($curDoc, '/');
            $url            = rtrim($this->getServerUrl(), '/') . '/'
                            . $curDoc
                            . (empty($curDoc) ? '' : '/') . $href;
        }

        return $this->xmlEscape($url);
    }

    /**
     * Returns a DOMDocument containing the Sitemap XML for the given container
     *
     * @param  Container                 $container  [optional] container to get
     *                                               breadcrumbs from, defaults
     *                                               to what is registered in the
     *                                               helper
     * @return DOMDocument                           DOM representation of the
     *                                               container
     * @throws Exception\RuntimeException            if schema validation is on
     *                                               and the sitemap is invalid
     *                                               according to the sitemap
     *                                               schema, or if sitemap
     *                                               validators are used and the
     *                                               loc element fails validation
     */
    public function getDomSitemap(Container $container = null)
    {
        if (null === $container) {
            $container = $this->getContainer();
        }

        // check if we should validate using our own validators
        if ($this->getUseSitemapValidators()) {
            // create validators
            $locValidator        = new \Zend\Validator\Sitemap\Loc();
            $lastmodValidator    = new \Zend\Validator\Sitemap\Lastmod();
            $changefreqValidator = new \Zend\Validator\Sitemap\Changefreq();
            $priorityValidator   = new \Zend\Validator\Sitemap\Priority();
        }

        // create document
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = $this->getFormatOutput();

        // ...and urlset (root) element
        $urlSet = $dom->createElementNS(self::SITEMAP_NS, 'urlset');
        $dom->appendChild($urlSet);

        // create iterator
        $iterator = new RecursiveIteratorIterator($container,
            RecursiveIteratorIterator::SELF_FIRST);

        $maxDepth = $this->getMaxDepth();
        if (is_int($maxDepth)) {
            $iterator->setMaxDepth($maxDepth);
        }
        $minDepth = $this->getMinDepth();
        if (!is_int($minDepth) || $minDepth < 0) {
            $minDepth = 0;
        }

        // iterate container
        foreach ($iterator as $page) {
            if ($iterator->getDepth() < $minDepth || !$this->accept($page)) {
                // page should not be included
                continue;
            }

            // get absolute url from page
            if (!$url = $this->url($page)) {
                // skip page if it has no url (rare case)
                continue;
            }

            // create url node for this page
            $urlNode = $dom->createElementNS(self::SITEMAP_NS, 'url');
            $urlSet->appendChild($urlNode);

            if ($this->getUseSitemapValidators()
                && !$locValidator->isValid($url)
            ) {
                throw new Exception\RuntimeException(sprintf(
                        'Encountered an invalid URL for Sitemap XML: "%s"',
                        $url
                ));
            }

            // put url in 'loc' element
            $urlNode->appendChild($dom->createElementNS(self::SITEMAP_NS,
                                                        'loc', $url));

            // add 'lastmod' element if a valid lastmod is set in page
            if (isset($page->lastmod)) {
                $lastmod = strtotime((string) $page->lastmod);

                // prevent 1970-01-01...
                if ($lastmod !== false) {
                    $lastmod = date('c', $lastmod);
                }

                if (!$this->getUseSitemapValidators() ||
                    $lastmodValidator->isValid($lastmod)) {
                    $urlNode->appendChild(
                        $dom->createElementNS(self::SITEMAP_NS, 'lastmod',
                                              $lastmod)
                    );
                }
            }

            // add 'changefreq' element if a valid changefreq is set in page
            if (isset($page->changefreq)) {
                $changefreq = $page->changefreq;
                if (!$this->getUseSitemapValidators() ||
                    $changefreqValidator->isValid($changefreq)) {
                    $urlNode->appendChild(
                        $dom->createElementNS(self::SITEMAP_NS, 'changefreq',
                                              $changefreq)
                    );
                }
            }

            // add 'priority' element if a valid priority is set in page
            if (isset($page->priority)) {
                $priority = $page->priority;
                if (!$this->getUseSitemapValidators() ||
                    $priorityValidator->isValid($priority)) {
                    $urlNode->appendChild(
                        $dom->createElementNS(self::SITEMAP_NS, 'priority',
                                              $priority)
                    );
                }
            }
        }

        // validate using schema if specified
        if ($this->getUseSchemaValidation()) {
            if (!@$dom->schemaValidate(self::SITEMAP_XSD)) {
                throw new Exception\RuntimeException(sprintf(
                        'Sitemap is invalid according to XML Schema at "%s"',
                        self::SITEMAP_XSD
                ));
            }
        }

        return $dom;
    }

    // Zend_View_Helper_Navigation_Helper:

    /**
     * Renders helper
     *
     * Implements {@link Helper::render()}.
     *
     * @param  Container $container [optional] container to render. Default is 
     *                              to render the container registered in the 
     *                              helper.
     * @return string               helper output
     */
    public function render(Container $container = null)
    {
        $dom = $this->getDomSitemap($container);

        $xml = $this->getUseXmlDeclaration() ?
               $dom->saveXML() :
               $dom->saveXML($dom->documentElement);

        return rtrim($xml, PHP_EOL);
    }
}
