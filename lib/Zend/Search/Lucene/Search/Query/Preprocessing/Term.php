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
	Zend\Search\Lucene\Index,
	Zend\Search\Lucene\Search\Query,
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
 * @uses       \Zend\Search\Lucene\Search\Query\EmptyResult
 * @uses       \Zend\Search\Lucene\Search\Query\Insignificant
 * @uses       \Zend\Search\Lucene\Search\Query\MultiTerm
 * @uses       \Zend\Search\Lucene\Search\Query\Preprocessing\AbstractPreprocessing
 * @uses       \Zend\Search\Lucene\Search\Query\Preprocessing\Term
 * @uses       \Zend\Search\Lucene\Search\Query\Term
 * @uses       \Zend\Search\Lucene\Search\Query\Wildcard
 * @category   Zend
 * @package    Zend_Search_Lucene
 * @subpackage Search
 * @internal
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Term extends AbstractPreprocessing
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
     * Class constructor.  Create a new preprocessing object for prase query.
     *
     * @param string $word       Non-tokenized word (query parser lexeme) to search.
     * @param string $encoding   Word encoding.
     * @param string $fieldName  Field name.
     */
    public function __construct($word, $encoding, $fieldName)
    {
        $this->_word     = $word;
        $this->_encoding = $encoding;
        $this->_field    = $fieldName;
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
            $query = new Query\MultiTerm();
            $query->setBoost($this->getBoost());

            $hasInsignificantSubqueries = false;

            if (Lucene\Lucene::getDefaultSearchField() === null) {
                $searchFields = $index->getFieldNames(true);
            } else {
                $searchFields = array(Lucene\Lucene::getDefaultSearchField());
            }

            foreach ($searchFields as $fieldName) {
                $subquery = new Term($this->_word,
                                     $this->_encoding,
                                     $fieldName);
                $rewrittenSubquery = $subquery->rewrite($index);
                foreach ($rewrittenSubquery->getQueryTerms() as $term) {
                    $query->addTerm($term);
                }

                if ($rewrittenSubquery instanceof Query\Insignificant) {
                    $hasInsignificantSubqueries = true;
                }
            }

            if (count($query->getTerms()) == 0) {
                $this->_matches = array();
                if ($hasInsignificantSubqueries) {
                    return new Query\Insignificant();
                } else {
                    return new Query\EmptyResult();
                }
            }

            $this->_matches = $query->getQueryTerms();
            return $query;
        }

        // -------------------------------------
        // Recognize exact term matching (it corresponds to Keyword fields stored in the index)
        // encoding is not used since we expect binary matching
        $term = new Index\Term($this->_word, $this->_field);
        if ($index->hasTerm($term)) {
            $query = new Query\Term($term);
            $query->setBoost($this->getBoost());

            $this->_matches = $query->getQueryTerms();
            return $query;
        }


        // -------------------------------------
        // Recognize wildcard queries

        /** @todo check for PCRE unicode support may be performed through Zend_Environment in some future */
        if (@preg_match('/\pL/u', 'a') == 1) {
            $word = iconv($this->_encoding, 'UTF-8', $this->_word);
            $wildcardsPattern = '/[*?]/u';
            $subPatternsEncoding = 'UTF-8';
        } else {
            $word = $this->_word;
            $wildcardsPattern = '/[*?]/';
            $subPatternsEncoding = $this->_encoding;
        }

        $subPatterns = preg_split($wildcardsPattern, $word, -1, PREG_SPLIT_OFFSET_CAPTURE);

        if (count($subPatterns) > 1) {
            // Wildcard query is recognized

            $pattern = '';

            foreach ($subPatterns as $id => $subPattern) {
                // Append corresponding wildcard character to the pattern before each sub-pattern (except first)
                if ($id != 0) {
                    $pattern .= $word[ $subPattern[1] - 1 ];
                }

                // Check if each subputtern is a single word in terms of current analyzer
                $tokens = Analyzer\Analyzer::getDefault()->tokenize($subPattern[0], $subPatternsEncoding);
                if (count($tokens) > 1) {
                    throw new QueryParserException('Wildcard search is supported only for non-multiple word terms');
                }
                foreach ($tokens as $token) {
                    $pattern .= $token->getTermText();
                }
            }

            $term  = new Index\Term($pattern, $this->_field);
            $query = new Query\Wildcard($term);
            $query->setBoost($this->getBoost());

            // Get rewritten query. Important! It also fills terms matching container.
            $rewrittenQuery = $query->rewrite($index);
            $this->_matches = $query->getQueryTerms();

            return $rewrittenQuery;
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
            $query = new Query\Term($term);
            $query->setBoost($this->getBoost());

            $this->_matches = $query->getQueryTerms();
            return $query;
        }

        //It's not insignificant or one term query
        $query = new Query\MultiTerm();

        /**
         * @todo Process $token->getPositionIncrement() to support stemming, synonyms and other
         * analizer design features
         */
        foreach ($tokens as $token) {
            $term = new Index\Term($token->getTermText(), $this->_field);
            $query->addTerm($term, true); // all subterms are required
        }

        $query->setBoost($this->getBoost());

        $this->_matches = $query->getQueryTerms();
        return $query;
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
            $word = iconv($this->_encoding, 'UTF-8', $this->_word);
            $wildcardsPattern = '/[*?]/u';
            $subPatternsEncoding = 'UTF-8';
        } else {
            $word = $this->_word;
            $wildcardsPattern = '/[*?]/';
            $subPatternsEncoding = $this->_encoding;
        }
        $subPatterns = preg_split($wildcardsPattern, $word, -1, PREG_SPLIT_OFFSET_CAPTURE);
        if (count($subPatterns) > 1) {
            // Wildcard query is recognized

            $pattern = '';

            foreach ($subPatterns as $id => $subPattern) {
                // Append corresponding wildcard character to the pattern before each sub-pattern (except first)
                if ($id != 0) {
                    $pattern .= $word[ $subPattern[1] - 1 ];
                }

                // Check if each subputtern is a single word in terms of current analyzer
                $tokens = Analyzer\Analyzer::getDefault()->tokenize($subPattern[0], $subPatternsEncoding);
                if (count($tokens) > 1) {
                    // Do nothing (nothing is highlighted)
                    return;
                }
                foreach ($tokens as $token) {
                    $pattern .= $token->getTermText();
                }
            }

            $term  = new Index\Term($pattern, $this->_field);
            $query = new Query\Wildcard($term);

            $query->_highlightMatches($highlighter);
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
            $highlighter->highlight($tokens[0]->getTermText());
            return;
        }

        //It's not insignificant or one term query
        $words = array();
        foreach ($tokens as $token) {
            $words[] = $token->getTermText();
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
