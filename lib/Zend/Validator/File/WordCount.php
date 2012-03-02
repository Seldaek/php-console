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
 * @category  Zend
 * @package   Zend_Validate
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Validator\File;

use Zend\Loader;

/**
 * Validator for counting all words in a file
 *
 * @uses      \Zend\Loader
 * @uses      \Zend\Validator\File\Count
 * @category  Zend
 * @package   Zend_Validate
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd     New BSD License
 */
class WordCount extends Count
{
    /**
     * @const string Error constants
     */
    const TOO_MUCH  = 'fileWordCountTooMuch';
    const TOO_LESS  = 'fileWordCountTooLess';
    const NOT_FOUND = 'fileWordCountNotFound';

    /**
     * @var array Error message templates
     */
    protected $_messageTemplates = array(
        self::TOO_MUCH => "Too much words, maximum '%max%' are allowed but '%count%' were counted",
        self::TOO_LESS => "Too less words, minimum '%min%' are expected but '%count%' were counted",
        self::NOT_FOUND => "File '%value%' is not readable or does not exist",
    );

    /**
     * Returns true if and only if the counted words are at least min and
     * not bigger than max (when max is not null).
     *
     * @param  string $value Filename to check for word count
     * @param  array  $file  File data from \Zend\File\Transfer\Transfer
     * @return boolean
     */
    public function isValid($value, $file = null)
    {
        if ($file === null) {
            $file = array('name' => basename($value));
        }

        // Is file readable ?
        if (!Loader::isReadable($value)) {
            return $this->_throw($file, self::NOT_FOUND);
        }

        $content = file_get_contents($value);
        $this->_count = str_word_count($content);
        if (($this->getMax() !== null) && ($this->_count > $this->getMax())) {
            return $this->_throw($file, self::TOO_MUCH);
        }

        if (($this->getMin() !== null) && ($this->_count < $this->getMin())) {
            return $this->_throw($file, self::TOO_LESS);
        }

        return true;
    }
}
