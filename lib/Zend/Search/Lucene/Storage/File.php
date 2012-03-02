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
 * @subpackage Storage
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Search\Lucene\Storage;
use Zend\Search\Lucene;

/**
 * @category   Zend
 * @package    Zend_Search_Lucene
 * @subpackage Storage
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
interface File
{
    /**
     * Sets the file position indicator and advances the file pointer.
     * The new position, measured in bytes from the beginning of the file,
     * is obtained by adding offset to the position specified by whence,
     * whose values are defined as follows:
     * SEEK_SET - Set position equal to offset bytes.
     * SEEK_CUR - Set position to current location plus offset.
     * SEEK_END - Set position to end-of-file plus offset. (To move to
     * a position before the end-of-file, you need to pass a negative value
     * in offset.)
     * Upon success, returns 0; otherwise, returns -1
     *
     * @param integer $offset
     * @param integer $whence
     * @return integer
     */
    public function seek($offset, $whence=\SEEK\SET);

    /**
     * Get file position.
     *
     * @return integer
     */
    public function tell();

    /**
     * Flush output.
     *
     * Returns true on success or false on failure.
     *
     * @return boolean
     */
    public function flush();

    /**
     * Lock file
     *
     * Lock type may be a LOCK_SH (shared lock) or a LOCK_EX (exclusive lock)
     *
     * @param integer $lockType
     * @return boolean
     */
    public function lock($lockType, $nonBlockinLock = false);

    /**
     * Unlock file
     */
    public function unlock();

    /**
     * Reads a byte from the current position in the file
     * and advances the file pointer.
     *
     * @return integer
     */
    public function readByte();

    /**
     * Writes a byte to the end of the file.
     *
     * @param integer $byte
     */
    public function writeByte($byte);

    /**
     * Read num bytes from the current position in the file
     * and advances the file pointer.
     *
     * @param integer $num
     * @return string
     */
    public function readBytes($num);

    /**
     * Writes num bytes of data (all, if $num===null) to the end
     * of the string.
     *
     * @param string $data
     * @param integer $num
     */
    public function writeBytes($data, $num=null);

    /**
     * Reads an integer from the current position in the file
     * and advances the file pointer.
     *
     * @return integer
     */
    public function readInt();

    /**
     * Writes an integer to the end of file.
     *
     * @param integer $value
     */
    public function writeInt($value);

    /**
     * Returns a long integer from the current position in the file
     * and advances the file pointer.
     *
     * @return integer|float
     */
    public function readLong();

    /**
     * Writes long integer to the end of file
     *
     * @param integer $value
     */
    public function writeLong($value);

    /**
     * Returns a variable-length integer from the current
     * position in the file and advances the file pointer.
     *
     * @return integer
     */
    public function readVInt();

    /**
     * Writes a variable-length integer to the end of file.
     *
     * @param integer $value
     */
    public function writeVInt($value);

    /**
     * Reads a string from the current position in the file
     * and advances the file pointer.
     *
     * @return string
     */
    public function readString();

    /**
     * Writes a string to the end of file.
     *
     * @param string $str
     */
    public function writeString($str);

    /**
     * Reads binary data from the current position in the file
     * and advances the file pointer.
     *
     * @return string
     */
    public function readBinary();
}
