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
 * @package    Zend_Feed_Reader
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
 
/**
* @namespace
*/
namespace Zend\Feed\Reader\Collection;

/**
* @uses \Zend\Feed\Reader\Collection\AbstractCollection
* @category Zend
* @package Zend_Feed_Reader
* @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
* @license http://framework.zend.com/license/new-bsd New BSD License
*/
class Author extends AbstractCollection
{

    /**
     * Return a simple array of the most relevant slice of
     * the author values, i.e. all author names.
     *
     * @return array
     */
    public function getValues() {
        $authors = array();
        foreach ($this->getIterator() as $element) {
            $authors[] = $element['name'];
        }
        return array_unique($authors);
    }

}
