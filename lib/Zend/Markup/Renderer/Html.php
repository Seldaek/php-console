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
 * @package    Zend_Markup
 * @subpackage Renderer
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Markup\Renderer;

use Zend\Markup\Renderer\Markup\Html\Root as RootMarkup;

/**
 * HTML renderer
 *
 * @category   Zend
 * @package    Zend_Markup
 * @subpackage Renderer
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Html extends AbstractRenderer
{

    /**
     * Constructor
     *
     * @param array|\Zend\Config\Config $options
     *
     * @todo make constructor compliant with new configuration standards
     *
     * @return void
     */
    public function __construct($options = array())
    {
        if ($options instanceof Config) {
            $options = $options->toArray();
        }

        if (isset($options['markups'])) {
            if (!isset($options['markups']['Zend_Markup_Root'])) {
                $options['markups'] = array(
                    'Zend_Markup_Root' => new RootMarkup()
                );
            }
        }

        parent::__construct($options);
    }
}
