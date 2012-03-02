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
 * @package    Zend_Search_Lucene
 * @subpackage Search
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Search\Lucene\Search\QueryEntry;

/**
 * @uses       \Zend\Search\Lucene\Search\QueryEntry\AbstractQueryEntry
 * @uses       \Zend\Search\Lucene\Search\Query\Fuzzy
 * @uses       \Zend\Search\Lucene\Search\Query\Preprocessing\Fuzzy
 * @uses       \Zend\Search\Lucene\Search\Query\Preprocessing\Term
 * @category   Zend
 * @package    Zend_Search_Lucene
 * @subpackage Search
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Term extends AbstractQueryEntry
{
    /**
     * Term value
     *
     * @var string
     */
    private $_term;

    /**
     * Field
     *
     * @var string|null
     */
    private $_field;


    /**
     * Fuzzy search query
     *
     * @var boolean
     */
    private $_fuzzyQuery = false;

    /**
     * Similarity
     *
     * @var float
     */
    private $_similarity = 1.;


    /**
     * Object constractor
     *
     * @param string $term
     * @param string $field
     */
    public function __construct($term, $field)
    {
        $this->_term  = $term;
        $this->_field = $field;
    }

    /**
     * Process modifier ('~')
     *
     * @param mixed $parameter
     */
    public function processFuzzyProximityModifier($parameter = null)
    {
        $this->_fuzzyQuery = true;

        if ($parameter !== null) {
            $this->_similarity = $parameter;
        } else {
            $this->_similarity = \Zend\Search\Lucene\Search\Query\Fuzzy::DEFAULT_MIN_SIMILARITY;
        }
    }

    /**
     * Transform entry to a subquery
     *
     * @param string $encoding
     * @return \Zend\Search\Lucene\Search\Query\AbstractQuery
     * @throws \Zend\Search\Lucene\Search\Exception\QueryParserException
     */
    public function getQuery($encoding)
    {
        if ($this->_fuzzyQuery) {
            $query = new \Zend\Search\Lucene\Search\Query\Preprocessing\Fuzzy($this->_term,
                                                                             $encoding,
                                                                             ($this->_field !== null)?
                                                                                  iconv($encoding, 'UTF-8', $this->_field) :
                                                                                  null,
                                                                             $this->_similarity
                                                                             );
            $query->setBoost($this->_boost);
            return $query;
        }


        $query = new \Zend\Search\Lucene\Search\Query\Preprocessing\Term($this->_term,
                                                                        $encoding,
                                                                        ($this->_field !== null)?
                                                                              iconv($encoding, 'UTF-8', $this->_field) :
                                                                              null
                                                                        );
        $query->setBoost($this->_boost);
        return $query;
    }
}
