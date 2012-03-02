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
 * @package    Zend_Ldap
 * @subpackage RootDse
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Ldap\Node\RootDse;

use Zend\Ldap\Node\RootDse;

/**
 * Zend_Ldap_Node_RootDse provides a simple data-container for the RootDse node of
 * a Novell eDirectory server.
 *
 * @uses       \Zend\Ldap\Node\RootDse
 * @category   Zend
 * @package    Zend_Ldap
 * @subpackage RootDse
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class eDirectory extends RootDse
{
    /**
     * Determines if the extension is supported
     *
     * @param  string|array $oids oid(s) to check
     * @return boolean
     */
    public function supportsExtension($oids)
    {
        return $this->attributeHasValue('supportedExtension', $oids);
    }

    /**
     * Gets the vendorName.
     *
     * @return string|null
     */
    public function getVendorName()
    {
        return $this->getAttribute('vendorName', 0);
    }

    /**
     * Gets the vendorVersion.
     *
     * @return string|null
     */
    public function getVendorVersion()
    {
        return $this->getAttribute('vendorVersion', 0);
    }

    /**
     * Gets the dsaName.
     *
     * @return string|null
     */
    public function getDsaName()
    {
        return $this->getAttribute('dsaName', 0);
    }

    /**
     * Gets the server statistics "errors".
     *
     * @return string|null
     */
    public function getStatisticsErrors()
    {
        return $this->getAttribute('errors', 0);
    }

    /**
     * Gets the server statistics "securityErrors".
     *
     * @return string|null
     */
    public function getStatisticsSecurityErrors()
    {
        return $this->getAttribute('securityErrors', 0);
    }

    /**
     * Gets the server statistics "chainings".
     *
     * @return string|null
     */
    public function getStatisticsChainings()
    {
        return $this->getAttribute('chainings', 0);
    }

    /**
     * Gets the server statistics "referralsReturned".
     *
     * @return string|null
     */
    public function getStatisticsReferralsReturned()
    {
        return $this->getAttribute('referralsReturned', 0);
    }

    /**
     * Gets the server statistics "extendedOps".
     *
     * @return string|null
     */
    public function getStatisticsExtendedOps()
    {
        return $this->getAttribute('extendedOps', 0);
    }

    /**
     * Gets the server statistics "abandonOps".
     *
     * @return string|null
     */
    public function getStatisticsAbandonOps()
    {
        return $this->getAttribute('abandonOps', 0);
    }

    /**
     * Gets the server statistics "wholeSubtreeSearchOps".
     *
     * @return string|null
     */
    public function getStatisticsWholeSubtreeSearchOps()
    {
        return $this->getAttribute('wholeSubtreeSearchOps', 0);
    }

    /**
     * Gets the server type
     *
     * @return int
     */
    public function getServerType()
    {
        return self::SERVER_TYPE_EDIRECTORY;
    }
}
