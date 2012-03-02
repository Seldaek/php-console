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
 * @package    Zend Service
 * @subpackage AgileZen_Resources
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Zend\Service\AgileZen\Resources;

use Zend\Service\AgileZen\AgileZen,
    Zend\Service\AgileZen\Entity;

/**
 * @category   Zend
 * @package    Zend Service
 * @subpackage AgileZen_Resources
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Task extends Entity
{
    /**
     * Text
     * 
     * @var string 
     */
    protected $text;

    /**
     * Create time
     * 
     * @var string 
     */
    protected $createTime;

    /**
     * Status
     * 
     * @var string 
     */
    protected $status;

    /**
     * Finish time
     * 
     * @var string 
     */
    protected $finishTime;

    /**
     * Finished by
     * 
     * @var User
     */
    protected $finishedBy;

    /**
     * AgileZen service
     * 
     * @var AgileZen 
     */
    protected $service;

    /**
     * Project Id
     * 
     * @var integer 
     */
    protected $projectId;

    /**
     * Constructor
     * 
     * @param AgileZen $service
     * @param array $data 
     */
    public function __construct(AgileZen $service, array $data)
    {
        if (!array_key_exists('id', $data)) {
             throw new Exception\InvalidArgumentException("You must pass the id of the task");
        }
        
        $this->text       = $data['text'];
        $this->createTime = $data['createTime'];
        $this->status     = $data['status'];
        $this->projectId  = $data['projectId'];

        if (isset($data['finishTime'])) {
            $this->finishTime = $data['finishTime'];
        }

        if (isset($data['finishedBy']) && !empty($data['finishedBy'])) {
            $this->finishedBy = new User($service, $data['finishedBy']);
        }

        $this->service= $service;
        
        parent::__construct($data['id']);
    }
    /**
     * Get text
     * 
     * @return string 
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Get create time
     * 
     * @return string 
     */
    public function getCreateTime()
    {
        return $this->createTime;
    }

    /**
     * Get status
     * 
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get finish time
     * 
     * @return string 
     */
    public function getFinishTime()
    {
        return $this->finishTime;
    }

    /**
     * Get finished by
     * 
     * @return Zend\Service\AgileZen\Resources\User 
     */
    public function getFinishedBy()
    {
        return $this->finishedBy;
    }

    /**
     * Get the project's Id
     * 
     * @return integer 
     */
    public function getProjectId()
    {
        return $this->projectId;
    }
}
