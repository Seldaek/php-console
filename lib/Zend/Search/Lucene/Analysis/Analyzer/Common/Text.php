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
 * @subpackage Analysis
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Search\Lucene\Analysis\Analyzer\Common;
use Zend\Search\Lucene\Analysis;

/**
 * @uses       \Zend\Search\Lucene\Analysis\Token
 * @category   Zend
 * @package    Zend_Search_Lucene
 * @subpackage Analysis
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Text extends AbstractCommon
{
    /**
     * Current position in a stream
     *
     * @var integer
     */
    private $_position;

    /**
     * Reset token stream
     */
    public function reset()
    {
        $this->_position = 0;

        if ($this->_input === null) {
            return;
        }

        // convert input into ascii
        if (PHP_OS != 'AIX') {
            $this->_input = iconv($this->_encoding, 'ASCII//TRANSLIT', $this->_input);
        }
        $this->_encoding = 'ASCII';
    }

    /**
     * Tokenization stream API
     * Get next token
     * Returns null at the end of stream
     *
     * @return \Zend\Search\Lucene\Analysis\Token|null
     */
    public function nextToken()
    {
        if ($this->_input === null) {
            return null;
        }


        do {
            if (! preg_match('/[a-zA-Z]+/', $this->_input, $match, PREG_OFFSET_CAPTURE, $this->_position)) {
                // It covers both cases a) there are no matches (preg_match(...) === 0)
                // b) error occured (preg_match(...) === FALSE)
                return null;
            }

            $str = $match[0][0];
            $pos = $match[0][1];
            $endpos = $pos + strlen($str);

            $this->_position = $endpos;

            $token = $this->normalize(new Analysis\Token($str, $pos, $endpos));
        } while ($token === null); // try again if token is skipped

        return $token;
    }
}

