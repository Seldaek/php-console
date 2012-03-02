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
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Search\Lucene;

/**
 * @uses       \Zend\Search\Lucene\Document
 * @uses       \Zend\Search\Lucene\Index\DocsFilter
 * @uses       \Zend\Search\Lucene\Index\Term
 * @uses       \Zend\Search\Lucene\Index\TermsStream
 * @category   Zend
 * @package    Zend_Search_Lucene
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
interface SearchIndex extends Index\TermsStream
{
    /**
     * Get current generation number
     *
     * Returns generation number
     * 0 means pre-2.1 index format
     * -1 means there are no segments files.
     *
     * @param \Zend\Search\Lucene\Storage\Directory $directory
     * @return integer
     * @throws \Zend\Search\Lucene\Exception
     */
    public static function getActualGeneration(Storage\Directory $directory);

    /**
     * Get segments file name
     *
     * @param integer $generation
     * @return string
     */
    public static function getSegmentFileName($generation);

    /**
     * Get index format version
     *
     * @return integer
     */
    public function getFormatVersion();

    /**
     * Set index format version.
     * Index is converted to this format at the nearest upfdate time
     *
     * @param int $formatVersion
     * @throws \Zend\Search\Lucene\Exception
     */
    public function setFormatVersion($formatVersion);

    /**
     * Returns the Zend_Search_Lucene_Storage_Directory instance for this index.
     *
     * @return \Zend\Search\Lucene\Storage\Directory
     */
    public function getDirectory();

    /**
     * Returns the total number of documents in this index (including deleted documents).
     *
     * @return integer
     */
    public function count();

    /**
     * Returns one greater than the largest possible document number.
     * This may be used to, e.g., determine how big to allocate a structure which will have
     * an element for every document number in an index.
     *
     * @return integer
     */
    public function maxDoc();

    /**
     * Returns the total number of non-deleted documents in this index.
     *
     * @return integer
     */
    public function numDocs();

    /**
     * Checks, that document is deleted
     *
     * @param integer $id
     * @return boolean
     * @throws \Zend\Search\Lucene\Exception    Exception is thrown if $id is out of the range
     */
    public function isDeleted($id);

    /**
     * Retrieve index maxBufferedDocs option
     *
     * maxBufferedDocs is a minimal number of documents required before
     * the buffered in-memory documents are written into a new Segment
     *
     * @return integer
     */
    public function getMaxBufferedDocs();

    /**
     * Set index maxBufferedDocs option
     *
     * maxBufferedDocs is a minimal number of documents required before
     * the buffered in-memory documents are written into a new Segment
     *
     * @param integer $maxBufferedDocs
     */
    public function setMaxBufferedDocs($maxBufferedDocs);

    /**
     * Retrieve index maxMergeDocs option
     *
     * maxMergeDocs is a largest number of documents ever merged by addDocument().
     * Small values (e.g., less than 10,000) are best for interactive indexing,
     * as this limits the length of pauses while indexing to a few seconds.
     * Larger values are best for batched indexing and speedier searches.
     *
     * @return integer
     */
    public function getMaxMergeDocs();

    /**
     * Set index maxMergeDocs option
     *
     * maxMergeDocs is a largest number of documents ever merged by addDocument().
     * Small values (e.g., less than 10,000) are best for interactive indexing,
     * as this limits the length of pauses while indexing to a few seconds.
     * Larger values are best for batched indexing and speedier searches.
     *
     * @param integer $maxMergeDocs
     */
    public function setMaxMergeDocs($maxMergeDocs);

    /**
     * Retrieve index mergeFactor option
     *
     * mergeFactor determines how often segment indices are merged by addDocument().
     * With smaller values, less RAM is used while indexing,
     * and searches on unoptimized indices are faster,
     * but indexing speed is slower.
     * With larger values, more RAM is used during indexing,
     * and while searches on unoptimized indices are slower,
     * indexing is faster.
     * Thus larger values (> 10) are best for batch index creation,
     * and smaller values (< 10) for indices that are interactively maintained.
     *
     * @return integer
     */
    public function getMergeFactor();

    /**
     * Set index mergeFactor option
     *
     * mergeFactor determines how often segment indices are merged by addDocument().
     * With smaller values, less RAM is used while indexing,
     * and searches on unoptimized indices are faster,
     * but indexing speed is slower.
     * With larger values, more RAM is used during indexing,
     * and while searches on unoptimized indices are slower,
     * indexing is faster.
     * Thus larger values (> 10) are best for batch index creation,
     * and smaller values (< 10) for indices that are interactively maintained.
     *
     * @param integer $maxMergeDocs
     */
    public function setMergeFactor($mergeFactor);

    /**
     * Performs a query against the index and returns an array
     * of Zend_Search_Lucene_Search_QueryHit objects.
     * Input is a string or Zend_Search_Lucene_Search_Query.
     *
     * @param mixed $query
     * @return array \Zend\Search\Lucene\Search\QueryHit
     * @throws \Zend\Search\Lucene\Exception
     */
    public function find($query);

    /**
     * Returns a list of all unique field names that exist in this index.
     *
     * @param boolean $indexed
     * @return array
     */
    public function getFieldNames($indexed = false);

    /**
     * Returns a Zend_Search_Lucene_Document object for the document
     * number $id in this index.
     *
     * @param integer|\Zend\Search\Lucene\Search\QueryHit $id
     * @return \Zend\Search\Lucene\Document
     */
    public function getDocument($id);

    /**
     * Returns true if index contain documents with specified term.
     *
     * Is used for query optimization.
     *
     * @param \Zend\Search\Lucene\Index\Term $term
     * @return boolean
     */
    public function hasTerm(Index\Term $term);

    /**
     * Returns IDs of all the documents containing term.
     *
     * @param \Zend\Search\Lucene\Index\Term $term
     * @param \Zend\Search\Lucene\Index\DocsFilter|null $docsFilter
     * @return array
     */
    public function termDocs(Index\Term $term, $docsFilter = null);

    /**
     * Returns documents filter for all documents containing term.
     *
     * It performs the same operation as termDocs, but return result as
     * Zend_Search_Lucene_Index_DocsFilter object
     *
     * @param \Zend\Search\Lucene\Index\Term $term
     * @param \Zend\Search\Lucene\Index\DocsFilter|null $docsFilter
     * @return \Zend\Search\Lucene\Index\DocsFilter
     */
    public function termDocsFilter(Index\Term $term, $docsFilter = null);

    /**
     * Returns an array of all term freqs.
     * Return array structure: array( docId => freq, ...)
     *
     * @param \Zend\Search\Lucene\Index\Term $term
     * @param \Zend\Search\Lucene\Index\DocsFilter|null $docsFilter
     * @return integer
     */
    public function termFreqs(Index\Term $term, $docsFilter = null);

    /**
     * Returns an array of all term positions in the documents.
     * Return array structure: array( docId => array( pos1, pos2, ...), ...)
     *
     * @param \Zend\Search\Lucene\Index\Term $term
     * @param \Zend\Search\Lucene\Index\DocsFilter|null $docsFilter
     * @return array
     */
    public function termPositions(Index\Term $term, $docsFilter = null);

    /**
     * Returns the number of documents in this index containing the $term.
     *
     * @param \Zend\Search\Lucene\Index\Term $term
     * @return integer
     */
    public function docFreq(Index\Term $term);

    /**
     * Retrive similarity used by index reader
     *
     * @return \Zend\Search\Lucene\Search\Similarity
     */
    public function getSimilarity();

    /**
     * Returns a normalization factor for "field, document" pair.
     *
     * @param integer $id
     * @param string $fieldName
     * @return float
     */
    public function norm($id, $fieldName);

    /**
     * Returns true if any documents have been deleted from this index.
     *
     * @return boolean
     */
    public function hasDeletions();

    /**
     * Deletes a document from the index.
     * $id is an internal document id
     *
     * @param integer|\Zend\Search\Lucene\Search\QueryHit $id
     * @throws \Zend\Search\Lucene\Exception
     */
    public function delete($id);

    /**
     * Adds a document to this index.
     *
     * @param \Zend\Search\Lucene\Document $document
     */
    public function addDocument(Document $document);

    /**
     * Commit changes resulting from delete() or undeleteAll() operations.
     */
    public function commit();

    /**
     * Optimize index.
     *
     * Merges all segments into one
     */
    public function optimize();

    /**
     * Returns an array of all terms in this index.
     *
     * @return array
     */
    public function terms();

    /**
     * Undeletes all documents currently marked as deleted in this index.
     */
    public function undeleteAll();
}
