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
 * @package    Zend_Code
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Zend\Code;

class NameInformation
{
    protected $namespace = null;
    protected $uses = array();

    public function __construct($namespace = null, array $uses = array())
    {
        if ($namespace) {
            $this->setNamespace($namespace);
        }
        if ($uses) {
            $this->setUses($uses);
        }
    }

    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function hasNamespace()
    {
        return ($this->namespace != null);
    }

    public function setUses(array $uses)
    {
        $this->uses = array();
        $this->addUses($uses);
        return $this;
    }

    public function addUses(array $uses)
    {
        foreach ($uses as $use => $as) {
            if (is_int($use)) {
                $this->addUse($as);
            } elseif (is_string($use)) {
                $this->addUse($use, $as);
            }

        }
        return $this;
    }

    public function addUse($use, $as = null)
    {
        if (is_array($use) && array_key_exists('use', $use) && array_key_exists('as', $use)) {
            $uses = $use;
            $use  = $uses['use'];
            $as   = $uses['as'];
        }
        $use = trim($use, '\\');
        if ($as === null) {
            $as = trim($use, '\\');
            $nsSeparatorPosition = strrpos($as, '\\');
            if ($nsSeparatorPosition !== false && $nsSeparatorPosition !== 0 && $nsSeparatorPosition != strlen($as)) {
                $as = substr($as, $nsSeparatorPosition + 1);
            }
        }
        $this->uses[$use] = $as;
    }

    public function getUses()
    {
        return $this->uses;
    }

    public function resolveName($name)
    {
        if ($this->namespace && !$this->uses && strlen($name) > 0 && $name{0} != '\\') {
            return $this->namespace . '\\' . $name;
        }

        if (!$this->uses || strlen($name) <= 0 || $name{0} == '\\') {
            return ltrim($name, '\\');
        }

        if ($this->namespace || $this->uses) {
            $firstPart = $name;
            if (($firstPartEnd = strpos($firstPart, '\\')) !== false)  {
                $firstPart = substr($firstPart, 0, $firstPartEnd);
            } else {
                $firstPartEnd = strlen($firstPart);
            }
            if (($fqns = array_search($firstPart, $this->uses)) !== false) {
                return substr_replace($name, $fqns, 0, $firstPartEnd);
            }
            if ($this->namespace) {
                return $this->namespace . '\\' . $name;
            }
        }
        return $name;
    }

}
