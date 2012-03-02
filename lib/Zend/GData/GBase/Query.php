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
 * @package    Zend_Gdata
 * @subpackage GBase
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\GData\GBase;

/**
 * Assists in constructing queries for Google Base
 *
 * @link http://code.google.com/apis/base
 *
 * @uses       \Zend\GData\Query
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage GBase
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Query extends \Zend\GData\Query
{

    /**
     * Path to the customer items feeds on the Google Base server.
     */
    const GBASE_ITEM_FEED_URI = 'http://www.google.com/base/feeds/items';

    /**
     * Path to the snippets feeds on the Google Base server.
     */
    const GBASE_SNIPPET_FEED_URI = 'http://www.google.com/base/feeds/snippets';

    /**
     * The default URI for POST methods
     *
     * @var string
     */
    protected $_defaultFeedUri = self::GBASE_ITEM_FEED_URI;

    /**
     * @param string $value
     * @return \Zend\GData\GBase\Query Provides a fluent interface
     */
    public function setKey($value)
    {
        if ($value !== null) {
            $this->_params['key'] = $value;
        } else {
            unset($this->_params['key']);
        }
        return $this;
    }

    /**
     * @param string $value
     * @return \Zend\GData\GBase\ItemQuery Provides a fluent interface
     */
    public function setBq($value)
    {
        if ($value !== null) {
            $this->_params['bq'] = $value;
        } else {
            unset($this->_params['bq']);
        }
        return $this;
    }

    /**
     * @param string $value
     * @return \Zend\GData\GBase\ItemQuery Provides a fluent interface
     */
    public function setRefine($value)
    {
        if ($value !== null) {
            $this->_params['refine'] = $value;
        } else {
            unset($this->_params['refine']);
        }
        return $this;
    }

    /**
     * @param string $value
     * @return \Zend\GData\GBase\ItemQuery Provides a fluent interface
     */
    public function setContent($value)
    {
        if ($value !== null) {
            $this->_params['content'] = $value;
        } else {
            unset($this->_params['content']);
        }
        return $this;
    }

    /**
     * @param string $value
     * @return \Zend\GData\GBase\ItemQuery Provides a fluent interface
     */
    public function setOrderBy($value)
    {
        if ($value !== null) {
            $this->_params['orderby'] = $value;
        } else {
            unset($this->_params['orderby']);
        }
        return $this;
    }

    /**
     * @param string $value
     * @return \Zend\GData\GBase\ItemQuery Provides a fluent interface
     */
    public function setSortOrder($value)
    {
        if ($value !== null) {
            $this->_params['sortorder'] = $value;
        } else {
            unset($this->_params['sortorder']);
        }
        return $this;
    }

    /**
     * @param string $value
     * @return \Zend\GData\GBase\ItemQuery Provides a fluent interface
     */
    public function setCrowdBy($value)
    {
        if ($value !== null) {
            $this->_params['crowdby'] = $value;
        } else {
            unset($this->_params['crowdby']);
        }
        return $this;
    }

    /**
     * @param string $value
     * @return \Zend\GData\GBase\ItemQuery Provides a fluent interface
     */
    public function setAdjust($value)
    {
        if ($value !== null) {
            $this->_params['adjust'] = $value;
        } else {
            unset($this->_params['adjust']);
        }
        return $this;
    }

    /**
     * @return string key
     */
    public function getKey()
    {
        if (array_key_exists('key', $this->_params)) {
            return $this->_params['key'];
        } else {
            return null;
        }
    }

    /**
     * @return string bq
     */
    public function getBq()
    {
        if (array_key_exists('bq', $this->_params)) {
            return $this->_params['bq'];
        } else {
            return null;
        }
    }

    /**
     * @return string refine
     */
    public function getRefine()
    {
        if (array_key_exists('refine', $this->_params)) {
            return $this->_params['refine'];
        } else {
            return null;
        }
    }

    /**
     * @return string content
     */
    public function getContent()
    {
        if (array_key_exists('content', $this->_params)) {
            return $this->_params['content'];
        } else {
            return null;
        }
    }

    /**
     * @return string orderby
     */
    public function getOrderBy()
    {
        if (array_key_exists('orderby', $this->_params)) {
            return $this->_params['orderby'];
        } else {
            return null;
        }
    }

    /**
     * @return string sortorder
     */
    public function getSortOrder()
    {
        if (array_key_exists('sortorder', $this->_params)) {
            return $this->_params['sortorder'];
        } else {
            return null;
        }
    }

    /**
     * @return string crowdby
     */
    public function getCrowdBy()
    {
        if (array_key_exists('crowdby', $this->_params)) {
            return $this->_params['crowdby'];
        } else {
            return null;
        }
    }

    /**
     * @return string adjust
     */
    public function getAdjust()
    {
        if (array_key_exists('adjust', $this->_params)) {
            return $this->_params['adjust'];
        } else {
            return null;
        }
    }

}
