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
 * @package    Zend_Reflection
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Code\Reflection;

use Zend\Code\Reflection,
    Zend\Code\Scanner\DocBlockScanner,
    Zend\Code\Annotation\AnnotationManager;

/**
 * @uses       Reflector
 * @uses       \Zend\Code\Reflection\ReflectionDocblockTag
 * @category   Zend
 * @package    Zend_Reflection
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class DocBlockReflection implements Reflection
{
    /**
     * @var Reflector
     */
    protected $reflector = null;

    /**
     * @var string
     */
    protected $docComment = null;

    /**
     * @var DocBlock\TagManager
     */
    protected $tagManager = null;

    /**#@+
     * @var int
     */
    protected $startLine = null;
    protected $endLine   = null;
    /**#@-*/

    /**
     * @var string
     */
    protected $cleanDocComment = null;

    /**
     * @var string
     */
    protected $longDescription = null;

    /**
     * @var string
     */
    protected $shortDescription = null;

    /**
     * @var array
     */
    protected $tags = array();

    /**
     * @var bool
     */
    protected $isReflected = false;

    /**
     * Export reflection
     *
     * Reqired by the Reflector interface.
     *
     * @todo   What should this do?
     * @return void
     */
    public static function export()
    {

    }

    /**
     * Constructor
     *
     * @param Reflector|string $commentOrReflector
     * @return \Zend\Code\Reflection\DocBlockReflection
     */
    public function __construct($commentOrReflector, DocBlock\TagManager $tagManager = null)
    {
        $this->tagManager = $tagManager ?: new DocBlock\TagManager(DocBlock\TagManager::USE_DEFAULT_PROTOTYPES);

        if ($commentOrReflector instanceof \Reflector) {
            $this->reflector = $commentOrReflector;
            if (!method_exists($commentOrReflector, 'getDocComment')) {
                throw new Exception\InvalidArgumentException('Reflector must contain method "getDocComment"');
            }
            /* @var MethodReflection $commentOrReflector */
            $this->docComment = $commentOrReflector->getDocComment();

            // determine line numbers
            $lineCount = substr_count($this->docComment, "\n");
            $this->startLine = $this->reflector->getStartLine() - $lineCount - 1;
            $this->endLine   = $this->reflector->getStartLine() - 1;

        } elseif (is_string($commentOrReflector)) {
            $this->docComment = $commentOrReflector;
        } else {
            throw new Exception\InvalidArgumentException(get_class($this) . ' must have a (string) DocComment or a Reflector in the constructor');
        }

        if ($this->docComment == '') {
            throw new Exception\InvalidArgumentException('DocComment cannot be empty');
        }

        $this->reflect();
    }

    /**
     * Retrieve contents of docblock
     *
     * @return string
     */
    public function getContents()
    {
        $this->reflect();
        return $this->cleanDocComment;
    }

    /**
     * Get start line (position) of docblock
     *
     * @return int
     */
    public function getStartLine()
    {
        $this->reflect();
        return $this->startLine;
    }

    /**
     * Get last line (position) of docblock
     *
     * @return int
     */
    public function getEndLine()
    {
        $this->reflect();
        return $this->endLine;
    }

    /**
     * Get docblock short description
     *
     * @return string
     */
    public function getShortDescription()
    {
        $this->reflect();
        return $this->shortDescription;
    }

    /**
     * Get docblock long description
     *
     * @return string
     */
    public function getLongDescription()
    {
        $this->reflect();
        return $this->longDescription;
    }

    /**
     * Does the docblock contain the given annotation tag?
     *
     * @param  string $name
     * @return bool
     */
    public function hasTag($name)
    {
        $this->reflect();
        foreach ($this->tags as $tag) {
            if ($tag->getName() == $name) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieve the given docblock tag
     *
     * @param  string $name
     * @return \Zend\Code\Reflection\ReflectionDocblockTag|false
     */
    public function getTag($name)
    {
        $this->reflect();
        foreach ($this->tags as $tag) {
            if ($tag->getName() == $name) {
                return $tag;
            }
        }

        return false;
    }

    /**
     * Get all docblock annotation tags
     *
     * @param string $filter
     * @return array Array of \Zend\Code\Reflection\ReflectionDocblockTag
     */
    public function getTags($filter = null)
    {
        $this->reflect();
        if ($filter === null || !is_string($filter)) {
            return $this->tags;
        }

        $returnTags = array();
        foreach ($this->tags as $tag) {
            if ($tag->getName() == $filter) {
                $returnTags[] = $tag;
            }
        }
        return $returnTags;
    }

    /**
     * Parse the docblock
     *
     * @return void
     */
    protected function reflect()
    {
        if ($this->isReflected) {
            return;
        }

        $docComment = $this->docComment; // localize variable

        // create a clean docComment
        $this->cleanDocComment = preg_replace('#[ \t]*(?:\/\*\*|\*\/|\*)?[ ]{0,1}(.*)?#', '$1', $docComment);
        $this->cleanDocComment = ltrim($this->cleanDocComment, "\r\n"); // @todo should be changed to remove first and last empty line

        $scanner = new DocBlockScanner($docComment);
        $this->shortDescription = ltrim($scanner->getShortDescription());
        $this->longDescription  = ltrim($scanner->getLongDescription());
        foreach ($scanner->getTags() as $tag) {
            $this->tags[] = $this->tagManager->createTag(ltrim($tag['name'], '@'), ltrim($tag['value']));
        }
        $this->isReflected = true;
    }

    public function toString()
    {
        $str = "DocBlock [ /* DocBlock */ ] {" . PHP_EOL . PHP_EOL;
        $str .= "  - Tags [" . count($this->tags) . "] {" . PHP_EOL;

        foreach($this->tags AS $tag) {
            $str .= "    " . $tag;
        }

        $str .= "  }" . PHP_EOL;
        $str .= "}" . PHP_EOL;

        return $str;
    }

    /**
     * Serialize to string
     *
     * Required by the Reflector interface
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

}
