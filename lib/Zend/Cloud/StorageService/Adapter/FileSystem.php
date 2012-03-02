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
 * @package    Zend\Cloud\StorageService
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * namespace
 */
namespace Zend\Cloud\StorageService\Adapter;

use Zend\Cloud\StorageService\Adapter,
    Zend\Cloud\StorageService\Exception;

/**
 * FileSystem adapter for unstructured cloud storage.
 *
 * @category   Zend
 * @package    Zend\Cloud\StorageService
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class FileSystem implements Adapter
{

    /**
     * Options array keys for the file system adapter.
     */
    const LOCAL_DIRECTORY = 'local_directory';

    /**
     * The directory for the data
     * @var string
     */
    protected $_directory = null;

    /**
     * Constructor
     *
     * @param  array|Zend\Config\Config $options
     * @return void
     */
    public function __construct($options = array())
    {
        if ($options instanceof \Zend\Config\Config) {
            $options = $options->toArray();
        }

        if (!is_array($options)) {
            throw new Exception\InvalidArgumentException('Invalid options provided');
        }

        if (isset($options[self::LOCAL_DIRECTORY])) {
            $this->_directory = $options[self::LOCAL_DIRECTORY];
        } else {
            $this->_directory = realpath(sys_get_temp_dir());
        }
    }

    /**
     * Get an item from the storage service.
     *
     * TODO: Support streaming
     *
     * @param  string $path
     * @param  array $options
     * @return false|string
     */
    public function fetchItem($path, $options = array())
    {
        $filepath = $this->_getFullPath($path);
        $path     = realpath($filepath);

        if (!$path) {
            return false;
        }

        return file_get_contents($path);
    }

    /**
     * Store an item in the storage service.
     *
     * WARNING: This operation overwrites any item that is located at
     * $destinationPath.
     *
     * @TODO Support streams
     *
     * @param  string $destinationPath
     * @param  mixed $data
     * @param  array $options
     * @return void
     */
    public function storeItem($destinationPath, $data, $options = array())
    {
        $path = $this->_getFullPath($destinationPath);
        file_put_contents($path, $data);
        chmod($path, 0777);
    }

    /**
     * Delete an item in the storage service.
     *
     * @param  string $path
     * @param  array $options
     * @return void
     */
    public function deleteItem($path, $options = array())
    {
        if (!isset($path)) {
            return;
        }

        $filepath = $this->_getFullPath($path);
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    /**
     * Copy an item in the storage service to a given path.
     *
     * WARNING: This operation is *very* expensive for services that do not
     * support copying an item natively.
     *
     * @TODO Support streams for those services that don't support natively
     *
     * @param  string $sourcePath
     * @param  string $destination path
     * @param  array $options
     * @return void
     */
    public function copyItem($sourcePath, $destinationPath, $options = array())
    {
        copy($this->_getFullPath($sourcePath), $this->_getFullPath($destinationPath));
    }

    /**
     * Move an item in the storage service to a given path.
     *
     * WARNING: This operation is *very* expensive for services that do not
     * support moving an item natively.
     *
     * @TODO Support streams for those services that don't support natively
     *
     * @param  string $sourcePath
     * @param  string $destination path
     * @param  array $options
     * @return void
     */
    public function moveItem($sourcePath, $destinationPath, $options = array())
    {
        rename($this->_getFullPath($sourcePath), $this->_getFullPath($destinationPath));
    }

        /**
     * Rename an item in the storage service to a given name.
     *
     *
     * @param  string $path
     * @param  string $name
     * @param  array $options
     * @return void
     */
    public function renameItem($path, $name, $options = null)
    {
        rename(
            $this->_getFullPath($path),
            dirname($this->_getFullPath($path)) . DIRECTORY_SEPARATOR . $name
        );
    }

    /**
     * List items in the given directory in the storage service
     *
     * The $path must be a directory
     *
     *
     * @param  string $path Must be a directory
     * @param  array $options
     * @return array A list of item names
     */
    public function listItems($path, $options = null)
    {
        $listing = scandir($this->_getFullPath($path));

        // Remove the hidden navigation directories
        $listing = array_diff($listing, array('.', '..'));

        return $listing;
    }

    /**
     * Get a key/value array of metadata for the given path.
     *
     * @param  string $path
     * @param  array $options
     * @return array
     */
    public function fetchMetadata($path, $options = array())
    {
        $fullPath = $this->_getFullPath($path);
        $metadata = null;
        if (file_exists($fullPath)) {
            $metadata = stat(realpath($fullPath));
        }

        return isset($metadata) ? $metadata : false;
    }

    /**
     * Store a key/value array of metadata at the given path.
     * WARNING: This operation overwrites any metadata that is located at
     * $destinationPath.
     *
     * @param  string $destinationPath
     * @param  array $options
     * @return void
     */
    public function storeMetadata($destinationPath, $metadata, $options = array())
    {
        throw new Exception\OperationNotAvailableException('Storing metadata not implemented');
    }

    /**
     * Delete a key/value array of metadata at the given path.
     *
     * @param  string $path
     * @param  array $options
     * @return void
     */
    public function deleteMetadata($path)
    {
        throw new Exception\OperationNotAvailableException('Deleting metadata not implemented');
    }

    /**
     * Return the full path for the file.
     *
     * @param string $path
     * @return string
     */
    private function _getFullPath($path)
    {
        return $this->_directory . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * Get the concrete client.
     * @return strings
     */
    public function getClient()
    {
         return $this->_directory;
    }
}
