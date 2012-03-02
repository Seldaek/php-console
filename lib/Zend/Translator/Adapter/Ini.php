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
 * @package    Zend_Translator
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Translator\Adapter;

use Zend\Translator\Adapter\AbstractAdapter,
    Zend\Translator\Exception\InvalidArgumentException;

/**
 * @uses       \Zend\Locale\Locale
 * @uses       \Zend\Translator\Adapter\AbstractAdapter
 * @uses       \Zend\Translator\Exception\InvalidArgumentException
 * @category   Zend
 * @package    Zend_Translator
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Ini extends AbstractAdapter
{
    /**
     * Load translation data
     *
     * @param  string|array  $data
     * @param  string        $locale  Locale/Language to add data for, identical with locale identifier,
     *                                see Zend_Locale for more information
     * @param  array         $options OPTIONAL Options to use
     * @throws \Zend\Translator\Exception\InvalidArgumentException when Ini file not found
     * @return array
     */
    protected function _loadTranslationData($data, $locale, array $options = array())
    {
        $result[$locale] = array();
        if (!file_exists($data)) {
            throw new InvalidArgumentException("Ini file '".$data."' not found");
        }

        $inidata         = parse_ini_file($data, false);
        $result[$locale] = array_merge($result[$locale], $inidata);
        return $result;
    }

    /**
     * returns the adapters name
     *
     * @return string
     */
    public function toString()
    {
        return "Ini";
    }
}
