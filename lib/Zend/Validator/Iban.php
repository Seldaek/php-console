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
 * @package    Zend_Validate
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Zend\Validator;

use Traversable,
    Zend\Locale\Locale,
    Zend\Registry,
    Zend\Stdlib\IteratorToArray;

/**
 * Validates IBAN Numbers (International Bank Account Numbers)
 *
 * @category   Zend
 * @package    Zend_Validate
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Iban extends AbstractValidator
{
    const NOTSUPPORTED = 'ibanNotSupported';
    const FALSEFORMAT  = 'ibanFalseFormat';
    const CHECKFAILED  = 'ibanCheckFailed';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = array(
        self::NOTSUPPORTED => "Unknown country within the IBAN '%value%'",
        self::FALSEFORMAT  => "'%value%' has a false IBAN format",
        self::CHECKFAILED  => "'%value%' has failed the IBAN check",
    );

    /**
     * Optional locale
     *
     * @var string|Locale|null
     */
    protected $locale;

    /**
     * IBAN regexes by region
     *
     * @var array
     */
    protected $ibanregex = array(
        'AD' => '/^AD[0-9]{2}[0-9]{8}[A-Z0-9]{12}$/',
        'AT' => '/^AT[0-9]{2}[0-9]{5}[0-9]{11}$/',
        'BA' => '/^BA[0-9]{2}[0-9]{6}[0-9]{10}$/',
        'BE' => '/^BE[0-9]{2}[0-9]{3}[0-9]{9}$/',
        'BG' => '/^BG[0-9]{2}[A-Z]{4}[0-9]{4}[0-9]{2}[A-Z0-9]{8}$/',
        'CH' => '/^CH[0-9]{2}[0-9]{5}[A-Z0-9]{12}$/',
        'CS' => '/^CS[0-9]{2}[0-9]{3}[0-9]{15}$/',
        'CY' => '/^CY[0-9]{2}[0-9]{8}[A-Z0-9]{16}$/',
        'CZ' => '/^CZ[0-9]{2}[0-9]{4}[0-9]{16}$/',
        'DE' => '/^DE[0-9]{2}[0-9]{8}[0-9]{10}$/',
        'DK' => '/^DK[0-9]{2}[0-9]{4}[0-9]{10}$/',
        'EE' => '/^EE[0-9]{2}[0-9]{4}[0-9]{12}$/',
        'ES' => '/^ES[0-9]{2}[0-9]{8}[0-9]{12}$/',
        'FR' => '/^FR[0-9]{2}[0-9]{10}[A-Z0-9]{13}$/',
        'FI' => '/^FI[0-9]{2}[0-9]{6}[0-9]{8}$/',
        'GB' => '/^GB[0-9]{2}[A-Z]{4}[0-9]{14}$/',
        'GI' => '/^GI[0-9]{2}[A-Z]{4}[A-Z0-9]{15}$/',
        'GR' => '/^GR[0-9]{2}[0-9]{7}[A-Z0-9]{16}$/',
        'HR' => '/^HR[0-9]{2}[0-9]{7}[0-9]{10}$/',
        'HU' => '/^HU[0-9]{2}[0-9]{7}[0-9]{1}[0-9]{15}[0-9]{1}$/',
        'IE' => '/^IE[0-9]{2}[A-Z0-9]{4}[0-9]{6}[0-9]{8}$/',
        'IS' => '/^IS[0-9]{2}[0-9]{4}[0-9]{18}$/',
        'IT' => '/^IT[0-9]{2}[A-Z]{1}[0-9]{10}[A-Z0-9]{12}$/',
        'LI' => '/^LI[0-9]{2}[0-9]{5}[A-Z0-9]{12}$/',
        'LU' => '/^LU[0-9]{2}[0-9]{3}[A-Z0-9]{13}$/',
        'LT' => '/^LT[0-9]{2}[0-9]{5}[0-9]{11}$/',
        'LV' => '/^LV[0-9]{2}[A-Z]{4}[A-Z0-9]{13}$/',
        'MK' => '/^MK[0-9]{2}[A-Z]{3}[A-Z0-9]{10}[0-9]{2}$/',
        'MT' => '/^MT[0-9]{2}[A-Z]{4}[0-9]{5}[A-Z0-9]{18}$/',
        'NL' => '/^NL[0-9]{2}[A-Z]{4}[0-9]{10}$/',
        'NO' => '/^NO[0-9]{2}[0-9]{4}[0-9]{7}$/',
        'PL' => '/^PL[0-9]{2}[0-9]{8}[0-9]{16}$/',
        'PT' => '/^PT[0-9]{2}[0-9]{8}[0-9]{13}$/',
        'RO' => '/^RO[0-9]{2}[A-Z]{4}[A-Z0-9]{16}$/',
        'SE' => '/^SE[0-9]{2}[0-9]{3}[0-9]{17}$/',
        'SI' => '/^SI[0-9]{2}[0-9]{5}[0-9]{8}[0-9]{2}$/',
        'SK' => '/^SK[0-9]{2}[0-9]{4}[0-9]{16}$/',
        'TN' => '/^TN[0-9]{2}[0-9]{5}[0-9]{15}$/',
        'TR' => '/^TR[0-9]{2}[0-9]{5}[A-Z0-9]{17}$/'
    );

    /**
     * Sets validator options
     *
     * @param  null|string|Locale|array|Traversable $locale OPTIONAL
     * @return void
     */
    public function __construct($locale = null)
    {
        $options = array();
        if ($locale instanceof Traversable) {
            $locale = IteratorToArray::convert($locale);
        }

        if (is_array($locale)) {
            $options = $locale;
            if (array_key_exists('locale', $locale)) {
                $locale  = $locale['locale'];
                unset($options['locale']);
            } else {
                $locale = null;
            }
        }

        if (empty($locale) && ($locale !== false)) {
            if (Registry::isRegistered('Zend_Locale')) {
                $locale = Registry::get('Zend_Locale');
            }
        }

        if ($locale !== null) {
            $this->setLocale($locale);
        }
        
        parent::__construct($options);
    }

    /**
     * Returns the locale option
     *
     * @return string|Locale|null
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Sets the locale option
     *
     * @param  string|Locale $locale
     * @return Iban provides a fluent interface
     */
    public function setLocale($locale = null)
    {
        if ($locale !== false) {
            $locale = Locale::findLocale($locale);
            if (strlen($locale) < 4) {
                throw new Exception\InvalidArgumentException('Region must be given for IBAN validation');
            }
        }

        $this->locale = $locale;
        return $this;
    }

    /**
     * Returns true if $value is a valid IBAN
     *
     * @param  string $value
     * @return boolean
     */
    public function isValid($value)
    {
        $value = strtoupper($value);
        $this->setValue($value);

        if (empty($this->locale)) {
            $region = substr($value, 0, 2);
        } else {
            $region = new Locale($this->locale);
            $region = $region->getRegion();
        }

        if (!array_key_exists($region, $this->ibanregex)) {
            $this->setValue($region);
            $this->error(self::NOTSUPPORTED);
            return false;
        }

        if (!preg_match($this->ibanregex[$region], $value)) {
            $this->error(self::FALSEFORMAT);
            return false;
        }

        $format = substr($value, 4) . substr($value, 0, 4);
        $format = str_replace(
            array('A',  'B',  'C',  'D',  'E',  'F',  'G',  'H',  'I',  'J',  'K',  'L',  'M',
                  'N',  'O',  'P',  'Q',  'R',  'S',  'T',  'U',  'V',  'W',  'X',  'Y',  'Z'),
            array('10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22',
                  '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35'),
            $format);

        $temp = intval(substr($format, 0, 1));
        $len  = strlen($format);
        for ($x = 1; $x < $len; ++$x) {
            $temp *= 10;
            $temp += intval(substr($format, $x, 1));
            $temp %= 97;
        }

        if ($temp != 1) {
            $this->error(self::CHECKFAILED);
            return false;
        }

        return true;
    }
}
