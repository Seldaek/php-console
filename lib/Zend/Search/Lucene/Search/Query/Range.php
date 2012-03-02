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
namespace Zend\Search\Lucene\Search\Query;

use Zend\Search\Lucene,
	Zend\Search\Lucene\Index,
	Zend\Search\Lucene\Search\Highlighter,
	Zend\Search\Lucene\Exception\UnsupportedMethodCallException,
	Zend\Search\Lucene\Exception\InvalidArgumentException,
	Zend\Search\Lucene\Exception\RuntimeException,
	Zend\Search\Lucene\Exception\OutOfBoundsException;

/**
 * @uses       \Zend\Search\Lucene\Index
 * @uses       \Zend\Search\Lucene\Analysis\Analyzer\Analyzer
 * @uses       \Zend\Search\Lucene\Exception
 * @uses       \Zend\Search\Lucene\Index\Term
 * @uses       \Zend\Search\Lucene\Search\Query\AbstractQuery
 * @uses       \Zend\Search\Lucene\Search\Query\EmptyResult
 * @uses       \Zend\Search\Lucene\Search\Query\MultiTerm
 * @uses       \Zend\Search\Lucene\Search\Query\Term
 * @category   Zend
 * @package    Zend_Search_Lucene
 * @subpackage Search
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Range extends AbstractQuery
{
    /**
     * Lower term.
     *
     * @var \Zend\Search\Lucene\Index\Term
     */
    private $_lowerTerm;

    /**
     * Upper term.
     *
     * @var \Zend\Search\Lucene\Index\Term
     */
    private $_upperTerm;


    /**
     * Search field
     *
     * @var string
     */
    private $_field;

    /**
     * Inclusive
     *
     * @var boolean
     */
    private $_inclusive;

    /**
     * Matched terms.
     *
     * Matched terms list.
     * It's filled during the search (rewrite operation) and may be used for search result
     * post-processing
     *
     * Array of Zend_Search_Lucene_Index_Term objects
     *
     * @var array
     */
    private $_matches = null;


    /**
     * Zend_Search_Lucene_Search_Query_Range constructor.
     *
     * @param \Zend\Search\Lucene\Index\Term|null $lowerTerm
     * @param \Zend\Search\Lucene\Index\Term|null $upperTerm
     * @param boolean $inclusive
     * @throws \Zend\Search\Lucene\Exception\InvalidArgumentException
     */
    public function __construct($lowerTerm, $upperTerm, $inclusive)
    {
        if ($lowerTerm === null  &&  $upperTerm === null) {
            throw new InvalidArgumentException('At least one term must be non-null');
        }
        if ($lowerTerm !== null  &&  $upperTerm !== null  &&  $lowerTerm->field != $upperTerm->field) {
            throw new InvalidArgumentException('Both terms must be for the same field');
        }

        $this->_field     = ($lowerTerm !== null)? $lowerTerm->field : $upperTerm->field;
        $this->_lowerTerm = $lowerTerm;
        $this->_upperTerm = $upperTerm;
        $this->_inclusive = $inclusive;
    }

    /**
     * Get query field name
     *
     * @return string|null
     */
    public function getField()
    {
        return $this->_field;
    }

    /**
     * Get lower term
     *
     * @return \Zend\Search\Lucene\Index\Term|null
     */
    public function getLowerTerm()
    {
        return $this->_lowerTerm;
    }

    /**
     * Get upper term
     *
     * @return \Zend\Search\Lucene\Index\Term|null
     */
    public function getUpperTerm()
    {
        return $this->_upperTerm;
    }

    /**
     * Get upper term
     *
     * @return boolean
     */
    public function isInclusive()
    {
        return $this->_inclusive;
    }

    /**
     * Re-write query into primitive queries in the context of specified index
     *
     * @param \Zend\Search\Lucene\SearchIndex $index
     * @throws \Zend\Search\Lucene\Exception\OutOfBoundsException
     * @return \Zend\Search\Lucene\Search\Query\AbstractQuery
     */
    public function rewrite(Lucene\SearchIndex $index)
    {
        $this->_matches = array();

        if ($this->_field === null) {
            // Search through all fields
            $fields = $index->getFieldNames(true /* indexed fields list */);
        } else {
            $fields = array($this->_field);
        }

        $maxTerms = Lucene\Lucene::getTermsPerQueryLimit();
        foreach ($fields as $field) {
            $index->resetTermsStream();

            if ($this->_lowerTerm !== null) {
                $lowerTerm = new Index\Term($this->_lowerTerm->text, $field);

                $index->skipTo($lowerTerm);

                if (!$this->_inclusive  &&
                    $index->currentTerm() == $lowerTerm) {
                    // Skip lower term
                    $index->nextTerm();
                }
            } else {
                $index->skipTo(new Index\Term('', $field));
            }


            if ($this->_upperTerm !== null) {
                // Walk up to the upper term
                $upperTerm = new Index\Term($this->_upperTerm->text, $field);

                while ($index->currentTerm() !== null          &&
                       $index->currentTerm()->field == $field  &&
                       $index->currentTerm()->text  <  $upperTerm->text) {
                    $this->_matches[] = $index->currentTerm();

                    if ($maxTerms != 0  &&  count($this->_matches) > $maxTerms) {
                        throw new OutOfBoundsException('Terms per query limit is reached.');
                    }

                    $index->nextTerm();
                }

                if ($this->_inclusive  &&  $index->currentTerm() == $upperTerm) {
                    // Include upper term into result
                    $this->_matches[] = $upperTerm;
                }
            } else {
                // Walk up to the end of field data
                while ($index->currentTerm() !== null  &&  $index->currentTerm()->field == $field) {
                    $this->_matches[] = $index->currentTerm();

                    if ($maxTerms != 0  &&  count($this->_matches) > $maxTerms) {
                        throw new OutOfBoundsException('Terms per query limit is reached.');
                    }

                    $index->nextTerm();
                }
            }

            $index->closeTermsStream();
        }

        if (count($this->_matches) == 0) {
            return new EmptyResult();
        } else if (count($this->_matches) == 1) {
            return new Term(reset($this->_matches));
        } else {
            $rewrittenQuery = new MultiTerm();

            foreach ($this->_matches as $matchedTerm) {
                $rewrittenQuery->addTerm($matchedTerm);
            }

            return $rewrittenQuery;
        }
    }

    /**
     * Optimize query in the context of specified index
     *
     * @param \Zend\Search\Lucene\SearchIndex $index
     * @throws \Zend\Search\Lucene\Exception\UnsupportedMethodCallException
     * @return \Zend\Search\Lucene\Search\Query\AbstractQuery
     */
    public function optimize(Lucene\SearchIndex $index)
    {
        throw new UnsupportedMethodCallException(
        	'Range query should not be directly used for search. Use $query->rewrite($index)'
        );
    }

    /**
     * Return query terms
     *
     * @return array
     * @throws \Zend\Search\Lucene\Exception\RuntimeException
     */
    public function getQueryTerms()
    {
        if ($this->_matches === null) {
            throw new RuntimeException('Search or rewrite operations have to be performed before.');
        }

        return $this->_matches;
    }

    /**
     * Constructs an appropriate Weight implementation for this query.
     *
     * @param \Zend\Search\Lucene\SearchIndex $reader
     * @throws \Zend\Search\Lucene\Exception\UnsupportedMethodCallException
     * @return \Zend\Search\Lucene\Search\Weight\Weight
     */
    public function createWeight(Lucene\SearchIndex $reader)
    {
        throw new UnsupportedMethodCallException(
        	'Range query should not be directly used for search. Use $query->rewrite($index)'
        );
    }


    /**
     * Execute query in context of index reader
     * It also initializes necessary internal structures
     *
     * @param \Zend\Search\Lucene\SearchIndex $reader
     * @param \Zend\Search\Lucene\Index\DocsFilter|null $docsFilter
     * @throws \Zend\Search\Lucene\Exception\UnsupportedMethodCallException
     */
    public function execute(Lucene\SearchIndex $reader, $docsFilter = null)
    {
        throw new UnsupportedMethodCallException(
        	'Range query should not be directly used for search. Use $query->rewrite($index)'
        );
    }

    /**
     * Get document ids likely matching the query
     *
     * It's an array with document ids as keys (performance considerations)
     *
     * @throws \Zend\Search\Lucene\Exception\UnsupportedMethodCallException
     * @return array
     */
    public function matchedDocs()
    {
        throw new UnsupportedMethodCallException(
        	'Range query should not be directly used for search. Use $query->rewrite($index)'
        );
    }

    /**
     * Score specified document
     *
     * @param integer $docId
     * @param \Zend\Search\Lucene\SearchIndex $reader
     * @throws \Zend\Search\Lucene\Exception\UnsupportedMethodCallException
     * @return float
     */
    public function score($docId, Lucene\SearchIndex $reader)
    {
        throw new UnsupportedMethodCallException(
        	'Range query should not be directly used for search. Use $query->rewrite($index)'
        );
    }

    /**
     * Query specific matches highlighting
     *
     * @param \Zend\Search\Lucene\Search\Highlighter $highlighter  Highlighter object (also contains doc for highlighting)
     */
    protected function _highlightMatches(Highlighter $highlighter)
    {
        $words = array();

        $docBody = $highlighter->getDocument()->getFieldUtf8Value('body');
        $tokens = Lucene\Analysis\Analyzer\Analyzer::getDefault()->tokenize($docBody, 'UTF-8');

        $lowerTermText = ($this->_lowerTerm !== null)? $this->_lowerTerm->text : null;
        $upperTermText = ($this->_upperTerm !== null)? $this->_upperTerm->text : null;

        if ($this->_inclusive) {
            foreach ($tokens as $token) {
                $termText = $token->getTermText();
                if (($lowerTermText == null  ||  $lowerTermText <= $termText)  &&
                    ($upperTermText == null  ||  $termText <= $upperTermText)) {
                    $words[] = $termText;
                }
            }
        } else {
            foreach ($tokens as $token) {
                $termText = $token->getTermText();
                if (($lowerTermText == null  ||  $lowerTermText < $termText)  &&
                    ($upperTermText == null  ||  $termText < $upperTermText)) {
                    $words[] = $termText;
                }
            }
        }

        $highlighter->highlight($words);
    }

    /**
     * Print a query
     *
     * @return string
     */
    public function __toString()
    {
        // It's used only for query visualisation, so we don't care about characters escaping
        return (($this->_field === null)? '' : $this->_field . ':')
             . (($this->_inclusive)? '[' : '{')
             . (($this->_lowerTerm !== null)?  $this->_lowerTerm->text : 'null')
             . ' TO '
             . (($this->_upperTerm !== null)?  $this->_upperTerm->text : 'null')
             . (($this->_inclusive)? ']' : '}')
             . (($this->getBoost() != 1)? '^' . round($this->getBoost(), 4) : '');
    }
}
