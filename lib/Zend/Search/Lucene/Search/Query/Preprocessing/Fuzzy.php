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
namespace Zend\Search\Lucene\Search\Query\Preprocessing;

use Zend\Search\Lucene,
	Zend\Search\Lucene\Search\Query,
	Zend\Search\Lucene\Index,
	Zend\Search\Lucene\Search,
	Zend\Search\Lucene\Analysis\Analyzer,
	Zend\Search\Lucene\Search\Highlighter,
	Zend\Search\Lucene\Search\Exception\QueryParserException;

/**
 * It's an internal abstract class intended to finalize ase a query processing after query parsing.
 * This type of query is not actually involved into query execution.
 *
 * @uses       \Zend\Search\Lucene\Index
 * @uses       \Zend\Search\Lucene\Analysis\Analyzer
 * @uses       \Zend\Search\Lucene\Index\Term
 * @uses       \Zend\Search\Lucene\Search\Exception\QueryParserException
 * @uses       \Zend\Search\Lucene\Search\Query\Boolean
 * @uses       \Zend\Search\Lucene\Search\Query\EmptyResult
 * @uses       \Zend\Search\Lucene\Search\Query\Fuzzy
 * @uses       \Zend\Search\Lucene\Search\Query\Insignificant
 * @uses       \Zend\Search\Lucene\Search\Query\Preprocessing\AbstractPreprocessing
 * @category   Zend
 * @package    Zend_Search_Lucene
 * @subpackage Search
 * @internal
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Fuzzy extends AbstractPreprocessing
{
    /**
     * word (query parser lexeme) to find.
     *
     * @var string
     */
    private $_word;

    /**
     * Word encoding (field name is always provided using UTF-8 encoding since it may be retrieved from index).
     *
     * @var string
     */
    private $_encoding;


    /**
     * Field name.
     *
     * @var string
     */
    private $_field;

    /**
     * A value between 0 and 1 to set the required similarity
     *  between the query term and the matching terms. For example, for a
     *  _minimumSimilarity of 0.5 a term of the same length
     *  as the query term is considered similar to the query term if the edit distance
     *  between both terms is less than length(term)*0.5
     *
     * @var float
     */
    private $_minimumSimilarity;

    /**
     * Class constructor.  Create a new preprocessing object for prase query.
     *
     * @param string $word       Non-tokenized word (query parser lexeme) to search.
     * @param string $encoding   Word encoding.
     * @param string $fieldName  Field name.
     * @param float  $minimumSimilarity minimum similarity
     */
    public function __construct($word, $encoding, $fieldName, $minimumSimilarity)
    {
        $this->_word     = $word;
        $this->_encoding = $encoding;
        $this->_field    = $fieldName;
        $this->_minimumSimilarity = $minimumSimilarity;
    }

    /**
     * Re-write query into primitive queries in the context of specified index
     *
     * @param \Zend\Search\Lucene\SearchIndex $index
     * @throws \Zend\Search\Lucene\Search\Exception\QueryParserException
     * @return \Zend\Search\Lucene\Search\Query\AbstractQuery
     */
    public function rewrite(Lucene\SearchIndex $index)
    {
        if ($this->_field === null) {
            $query = new Search\Query\Boolean();

            $hasInsignificantSubqueries = false;

            if (Lucene\Lucene::getDefaultSearchField() === null) {
                $searchFields = $index->getFieldNames(true);
            } else {
                $searchFields = array(Lucene\Lucene::getDefaultSearchField());
            }

            foreach ($searchFields as $fieldName) {
                $subquery = new self($this->_word,
                                     $this->_encoding,
                                     $fieldName,
                                     $this->_minimumSimilarity);

                $rewrittenSubquery = $subquery->rewrite($index);

                if ( !($rewrittenSubquery instanceof Query\Insignificant  ||
                       $rewrittenSubquery instanceof Query\EmptyResult) ) {
                    $query->addSubquery($rewrittenSubquery);
                }

                if ($rewrittenSubquery instanceof Query\Insignificant) {
                    $hasInsignificantSubqueries = true;
                }
            }

            $subqueries = $query->getSubqueries();

            if (count($subqueries) == 0) {
                $this->_matches = array();
                if ($hasInsignificantSubqueries) {
                    return new Query\Insignificant();
                } else {
                    return new Query\EmptyResult();
                }
            }

            if (count($subqueries) == 1) {
                $query = reset($subqueries);
            }

            $query->setBoost($this->getBoost());

            $this->_matches = $query->getQueryTerms();
            return $query;
        }

        // -------------------------------------
        // Recognize exact term matching (it corresponds to Keyword fields stored in the index)
        // encoding is not used since we expect binary matching
        $term = new Index\Term($this->_word, $this->_field);
        if ($index->hasTerm($term)) {
            $query = new Query\Fuzzy($term, $this->_minimumSimilarity);
            $query->setBoost($this->getBoost());

            // Get rewritten query. Important! It also fills terms matching container.
            $rewrittenQuery = $query->rewrite($index);
            $this->_matches = $query->getQueryTerms();

            return $rewrittenQuery;
        }


        // -------------------------------------
        // Recognize wildcard queries

        /** @todo check for PCRE unicode support may be performed through Zend_Environment in some future */
        if (@preg_match('/\pL/u', 'a') == 1) {
            $subPatterns = preg_split('/[*?]/u', iconv($this->_encoding, 'UTF-8', $this->_word));
        } else {
            $subPatterns = preg_split('/[*?]/', $this->_word);
        }
        if (count($subPatterns) > 1) {
            throw new QueryParserException('Fuzzy search doesn\'t support wildcards (except within Keyword fields).');
        }


        // -------------------------------------
        // Recognize one-term multi-term and "insignificant" queries
        $tokens = Analyzer\Analyzer::getDefault()->tokenize($this->_word, $this->_encoding);

        if (count($tokens) == 0) {
            $this->_matches = array();
            return new Query\Insignificant();
        }

        if (count($tokens) == 1) {
            $term  = new Index\Term($tokens[0]->getTermText(), $this->_field);
            $query = new Query\Fuzzy($term, $this->_minimumSimilarity);
            $query->setBoost($this->getBoost());

            // Get rewritten query. Important! It also fills terms matching container.
            $rewrittenQuery = $query->rewrite($index);
            $this->_matches = $query->getQueryTerms();

            return $rewrittenQuery;
        }

        // Word is tokenized into several tokens
        throw new QueryParserException('Fuzzy search is supported only for non-multiple word terms');
    }

    /**
     * Query specific matches highlighting
     *
     * @param \Zend\Search\Lucene\Search\Highlighter $highlighter  Highlighter object (also contains doc for highlighting)
     */
    protected function _highlightMatches(Highlighter $highlighter)
    {
        /** Skip fields detection. We don't need it, since we expect all fields presented in the HTML body and don't differentiate them */

        /** Skip exact term matching recognition, keyword fields highlighting is not supported */

        // -------------------------------------
        // Recognize wildcard queries

        /** @todo check for PCRE unicode support may be performed through Zend_Environment in some future */
        if (@preg_match('/\pL/u', 'a') == 1) {
            $subPatterns = preg_split('/[*?]/u', iconv($this->_encoding, 'UTF-8', $this->_word));
        } else {
            $subPatterns = preg_split('/[*?]/', $this->_word);
        }
        if (count($subPatterns) > 1) {
            // Do nothing
            return;
        }


        // -------------------------------------
        // Recognize one-term multi-term and "insignificant" queries
        $tokens = Analyzer\Analyzer::getDefault()->tokenize($this->_word, $this->_encoding);
        if (count($tokens) == 0) {
            // Do nothing
            return;
        }
        if (count($tokens) == 1) {
            $term  = new Index\Term($tokens[0]->getTermText(), $this->_field);
            $query = new Query\Fuzzy($term, $this->_minimumSimilarity);

            $query->_highlightMatches($highlighter);
            return;
        }

        // Word is tokenized into several tokens
        // But fuzzy search is supported only for non-multiple word terms
        // Do nothing
    }

    /**
     * Print a query
     *
     * @return string
     */
    public function __toString()
    {
        // It's used only for query visualisation, so we don't care about characters escaping
        if ($this->_field !== null) {
            $query = $this->_field . ':';
        } else {
            $query = '';
        }

        $query .= $this->_word;

        if ($this->getBoost() != 1) {
            $query .= '^' . round($this->getBoost(), 4);
        }

        return $query;
    }
}
