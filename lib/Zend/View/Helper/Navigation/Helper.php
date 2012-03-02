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
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Zend\View\Helper\Navigation;

use Zend\Acl,
    Zend\Navigation;

/**
 * Interface for navigational helpers
 *
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
interface Helper
{
    /**
     * Sets navigation container the helper should operate on by default
     *
     * @param  Navigation\Container $container [optional] container to operate 
     *                                         on. Default is null, which 
     *                                         indicates that the container 
     *                                         should be reset.
     * @return Helper fluent interface, returns self
     */
    public function setContainer(Navigation\Container $container = null);

    /**
     * Returns the navigation container the helper operates on by default
     *
     * @return Navigation\Container  navigation container
     */
    public function getContainer();

    /**
     * Sets translator to use in helper
     *
     * @param  mixed $translator [optional] translator.  Expects an object of 
     *                           type {@link \Zend\Translator\Adapter} or 
     *                           {@link \Zend\Translator\Translator}, or null. 
     *                           Default is null.
     * @return Helper  fluent interface, returns self
     */
    public function setTranslator($translator = null);

    /**
     * Returns translator used in helper
     *
     * @return \Zend\Translator\Adapter|null  translator or null
     */
    public function getTranslator();

    /**
     * Sets ACL to use when iterating pages
     *
     * @param  Acl\Acl $acl [optional] ACL instance
     * @return Helper  fluent interface, returns self
     */
    public function setAcl(Acl\Acl $acl = null);

    /**
     * Returns ACL or null if it isn't set using {@link setAcl()} or
     * {@link setDefaultAcl()}
     *
     * @return Acl\Acl|null  ACL object or null
     */
    public function getAcl();

    /**
     * Sets ACL role to use when iterating pages
     *
     * @param  mixed $role [optional] role to set.  Expects a string, an 
     *                     instance of type {@link Acl\Role}, or null. Default 
     *                     is null.
     * @throws \Zend\View\Exception if $role is invalid
     * @return Helper fluent interface, returns
     *                                             self
     */
    public function setRole($role = null);

    /**
     * Returns ACL role to use when iterating pages, or null if it isn't set
     *
     * @return string|Acl\Role|null  role or null
     */
    public function getRole();

    /**
     * Sets whether ACL should be used
     *
     * @param  bool $useAcl [optional] whether ACL should be used. Default is true.
     * @return Helper  fluent interface, returns self
     */
    public function setUseAcl($useAcl = true);

    /**
     * Returns whether ACL should be used
     *
     * @return bool  whether ACL should be used
     */
    public function getUseAcl();

    /**
     * Return renderInvisible flag
     *
     * @return bool
     */
    public function getRenderInvisible();

    /**
     * Render invisible items?
     *
     * @param  bool $renderInvisible [optional] boolean flag
     * @return Helper  fluent interface returns self
     */
    public function setRenderInvisible($renderInvisible = true);

    /**
     * Sets whether translator should be used
     *
     * @param  bool $useTranslator [optional] whether translator should be used.
     *                             Default is true.
     * @return Helper  fluent interface, returns self
     */
    public function setUseTranslator($useTranslator = true);

    /**
     * Returns whether translator should be used
     *
     * @return bool  whether translator should be used
     */
    public function getUseTranslator();

    /**
     * Checks if the helper has a container
     *
     * @return bool  whether the helper has a container or not
     */
    public function hasContainer();

    /**
     * Checks if the helper has an ACL instance
     *
     * @return bool  whether the helper has a an ACL instance or not
     */
    public function hasAcl();

    /**
     * Checks if the helper has an ACL role
     *
     * @return bool  whether the helper has a an ACL role or not
     */
    public function hasRole();

    /**
     * Checks if the helper has a translator
     *
     * @return bool  whether the helper has a translator or not
     */
    public function hasTranslator();

    /**
     * Magic overload: Should proxy to {@link render()}.
     *
     * @return string
     */
    public function __toString();

    /**
     * Renders helper
     *
     * @param  Navigation\Container $container [optional] container to render. 
     *                                         Default is null, which indicates 
     *                                         that the helper should render 
     *                                         the container returned by {@link 
     *                                         getContainer()}.
     * @return string helper output
     * @throws \Zend\View\Exception if unable to render
     */
    public function render(Navigation\Container $container = null);
}
