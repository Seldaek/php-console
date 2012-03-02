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
 * @package    Zend\Service\Rackspace
 * @subpackage Files
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Zend\Service\Rackspace\Files;

use Zend\Service\Rackspace\Files as RackspaceFiles;

class Object
{
    /**
     * The service that has created the object
     *
     * @var Zend\Service\Rackspace\Files
     */
    protected $service;
    /**
     * Name of the object
     *
     * @var string
     */
    protected $name;
    /**
     * MD5 value of the object's content
     *
     * @var string
     */
    protected $hash;
    /**
     * Size in bytes of the object's content
     *
     * @var integer
     */
    protected $size;
    /**
     * Content type of the object's content
     *
     * @var string
     */
    protected $contentType;
    /**
     * Date of the last modified of the object
     *
     * @var string
     */
    protected $lastModified;
    /**
     * Object content
     *
     * @var string
     */
    protected $content;
    /**
     * Name of the container where the object is stored
     *
     * @var string
     */
    protected $container;
    /**
     * Constructor
     * 
     * You must pass the RackspaceFiles object of the caller and an associative
     * array with the keys "name", "container", "hash", "bytes", "content_type",
     * "last_modified", "file" where:
     * name= name of the object
     * container= name of the container where the object is stored
     * hash= the MD5 of the object's content
     * bytes= size in bytes of the object's content
     * content_type= content type of the object's content
     * last_modified= date of the last modified of the object
     * content= content of the object
     * 
     * @param RackspaceFiles $service
     * @param array $data 
     */
    public function __construct(RackspaceFiles $service,$data)
    {
        if (!($service instanceof RackspaceFiles) || !is_array($data)) {
             throw new Exception\InvalidArgumentException("You must pass a RackspaceFiles and an array");
        }
        if (!array_key_exists('name', $data)) {
             throw new Exception\InvalidArgumentException("You must pass the name of the object in the array (name)");
        }
        if (!array_key_exists('container', $data)) {
             throw new Exception\InvalidArgumentException("You must pass the container of the object in the array (container)");
        }
        if (!array_key_exists('hash', $data)) {
             throw new Exception\InvalidArgumentException("You must pass the hash of the object in the array (hash)");
        }
        if (!array_key_exists('bytes', $data)) {
             throw new Exception\InvalidArgumentException("You must pass the byte size of the object in the array (bytes)");
        }
        if (!array_key_exists('content_type', $data)) {
             throw new Exception\InvalidArgumentException("You must pass the content type of the object in the array (content_type)");
        }
        if (!array_key_exists('last_modified', $data)) {
             throw new Exception\InvalidArgumentException("You must pass the last modified data of the object in the array (last_modified)");
        }
        $this->name= $data['name'];
        $this->container= $data['container'];
        $this->hash= $data['hash'];
        $this->size= $data['bytes'];
        $this->contentType= $data['content_type'];
        $this->lastModified= $data['last_modified'];
        if (!empty($data['content'])) {
            $this->content= $data['content'];
        }
        $this->service= $service;
    }
    /**
     * Get name
     *
     * @return string
     */
    public function getName() 
    {
        return $this->name;
    }
    /**
     * Get the name of the container
     *
     * @return string
     */
    public function getContainer() 
    {
        return $this->container;
    }
    /**
     * Get the MD5 of the object's content
     *
     * @return string|boolean
     */
    public function getHash() 
    {
        return $this->hash;
    }
    /**
     * Get the size (in bytes) of the object's content
     *
     * @return integer|boolean
     */
    public function getSize() 
    {
        return $this->size;
    }
    /**
     * Get the content type of the object's content
     *
     * @return string
     */
    public function getContentType() 
    {
        return $this->contentType;
    }
    /**
     * Get the data of the last modified of the object
     *
     * @return string
     */
    public function getLastModified() 
    {
        return $this->lastModified;
    }
    /**
     * Get the content of the object
     *
     * @return string
     */
    public function getContent() 
    {
        return $this->content;
    }
    /**
     * Get the metadata of the object
     * If you don't pass the $key it returns the entire array of metadata value
     *
     * @param string $key
     * @return string|array|boolean
     */
    public function getMetadata($key=null) 
    {
        $result= $this->service->getMetadataObject($this->container,$this->name);
        if (!empty($result)) {
            if (empty($key)) {
                return $result['metadata'];
            }
            if (isset($result['metadata'][$key])) {
                return $result['metadata'][$key];
            }
        }
        return false;
    }
    /**
     * Set the metadata value
     * The old metadata values are replaced with the new one
     * 
     * @param array $metadata
     * @return boolean
     */
    public function setMetadata($metadata) 
    {
        return $this->service->setMetadataObject($this->container,$this->name,$metadata);
    }
    /**
     * Copy the object to another container
     * You can add metadata information to the destination object, change the
     * content_type and the name of the object
     *
     * @param string $container_dest
     * @param string $name_dest
     * @param array $metadata
     * @param string $content_type
     * @return boolean
     */
    public function copyTo($container_dest,$name_dest,$metadata=array(),$content_type=null) 
    {
        return $this->service->copyObject($this->container,$this->name,$container_dest,$name_dest,$metadata,$content_type);
    }
    /**
     * Get the CDN URL of the object
     *
     * @return string
     */
    public function getCdnUrl() 
    {
        $result= $this->service->getInfoCdnContainer($this->container);
        if ($result!==false) {
            if ($result['cdn_enabled']) {
                return $result['cdn_uri'].'/'.$this->name;
            }
        }
        return false;
    }
    /**
     * Get the CDN SSL URL of the object
     *
     * @return string
     */
    public function getCdnUrlSsl() 
    {
        $result= $this->service->getInfoCdnContainer($this->container);
        if ($result!==false) {
            if ($result['cdn_enabled']) {
                return $result['cdn_uri_ssl'].'/'.$this->name;
            }
        }
        return false;
    }
}
