<?php
/**
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
 * @package    Zend\Cloud\DocumentService\Adapter
 * @subpackage WindowsAzure
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * namespace
 */
namespace Zend\Cloud\DocumentService\Adapter\WindowsAzure;

use Zend\Cloud\DocumentService\QueryAdapter,
    Zend\Service\WindowsAzure\Storage\TableEntityQuery,
    Zend\Cloud\DocumentService\Adapter\Exception;

/**
 * Class implementing Query adapter for working with Azure queries in a
 * structured way
 *
 * @todo       Look into preventing a query injection attack.
 * @category   Zend
 * @package    Zend\Cloud\DocumentService\Adapter
 * @subpackage WindowsAzure
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Query implements QueryAdapter
{
    /**
     * Azure concrete query
     *
     * @var Zend\Service\WindowsAzure\Storage\TableEntityQuery
     */
    protected $_azureSelect;

    /**
     * Constructor
     *
     * @param  null|Zend\Service\WindowsAzure\Storage\TableEntityQuery $select Table select object
     * @return void
     */
    public function __construct($select = null)
    {
        if (!$select instanceof Zend\Service\WindowsAzure\Storage\TableEntityQuery) {
            $select = new TableEntityQuery();
        }
        $this->_azureSelect = $select;
    }

    /**
     * SELECT clause (fields to be selected)
     *
     * Does nothing for Azure.
     *
     * @param  string $select
     * @return Zend\Cloud\DocumentService\Adapter\WindowsAzure_Query
     */
    public function select($select)
    {
        return $this;
    }

    /**
     * FROM clause (table name)
     *
     * @param string $from
     * @return Zend\Cloud\DocumentService\Adapter\WindowsAzure\Query
     */
    public function from($from)
    {
        $this->_azureSelect->from($from);
        return $this;
    }

    /**
     * WHERE clause (conditions to be used)
     *
     * @param string $where
     * @param mixed $value Value or array of values to be inserted instead of ?
     * @param string $op Operation to use to join where clauses (AND/OR)
     * @return Zend\Cloud\DocumentService\Adapter\WindowsAzure\Query
     */
    public function where($where, $value = null, $op = 'and')
    {
        if (!empty($value) && !is_array($value)) {
            // fix buglet in Azure - numeric values are quoted unless passed as an array
            $value = array($value);
        }
        $this->_azureSelect->where($where, $value, $op);
        return $this;
    }

    /**
     * WHERE clause for item ID
     *
     * This one should be used when fetching specific rows since some adapters
     * have special syntax for primary keys
     *
     * @param  array $value Row ID for the document (PartitionKey, RowKey)
     * @return Zend\Cloud\DocumentService\Adapter\WindowsAzure\Query
     */
    public function whereId($value)
    {
        if (!is_array($value)) {
            throw new Exception\InvalidArgumentException('Invalid document key');
        }
        $this->_azureSelect->wherePartitionKey($value[0])->whereRowKey($value[1]);
        return $this;
    }

    /**
     * LIMIT clause (how many rows to return)
     *
     * @param  int $limit
     * @return Zend\Cloud\DocumentService\Adapter\WindowsAzure\Query
     */
    public function limit($limit)
    {
        $this->_azureSelect->top($limit);
        return $this;
    }

    /**
     * ORDER BY clause (sorting)
     *
     * @todo   Azure service doesn't seem to support this yet; emulate?
     * @param  string $sort Column to sort by
     * @param  string $direction Direction - asc/desc
     * @return Zend\Cloud\DocumentService\Adapter\WindowsAzure\Query
     * @throws Zend\Cloud\OperationNotAvailableException
     */
    public function order($sort, $direction = 'asc')
    {
        throw new Exception\OperationNotAvailableException('No support for sorting for Azure yet');
    }

    /**
     * Get Azure select query
     *
     * @return Zend\Service\WindowsAzure\Storage\TableEntityQuery
     */
    public function getAzureSelect()
    {
        return  $this->_azureSelect;
    }

    /**
     * Assemble query
     *
     * Simply return the WindowsAzure table entity query object
     *
     * @return Zend\Service\WindowsAzure\Storage\TableEntityQuery
     */
    public function assemble()
    {
        return $this->getAzureSelect();
    }
}
