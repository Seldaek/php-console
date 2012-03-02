<?php

namespace Zend\Code\Reflection\DocBlock;

use Zend\Code\Reflection\Exception;

class TagManager
{
    const USE_DEFAULT_PROTOTYPES = 'default';

    protected $tagNames = array();
    protected $tags = array();
    protected $genericTag = null;

    public function __construct($prototypes = null)
    {
        if (is_array($prototypes)) {
            foreach ($prototypes as $prototype) {
                $this->addTagPrototype($prototype);
            }
        } elseif ($prototypes === self::USE_DEFAULT_PROTOTYPES) {
            $this->useDefaultPrototypes();
        }
    }

    public function useDefaultPrototypes()
    {
        $this->addTagPrototype(new ParamTag());
        $this->addTagPrototype(new ReturnTag());
        $this->addTagPrototype(new GenericTag());
    }

    public function addTagPrototype(Tag $tag)
    {
        $tagName = strtolower(str_replace(array('-', '_'), '', $tag->getName()));

        if (in_array($tagName, $this->tagNames)) {
            throw new Exception\InvalidArgumentException('A tag with this name already exists in this manager');
        }

        $this->tagNames[] = $tagName;
        $this->tags[] = $tag;

        if ($tag instanceof GenericTag) {
            $this->genericTag = $tag;
        }
    }

    public function hasTag($tagName)
    {
        // otherwise, only if its name exists as a key
        return in_array(strtolower(str_replace(array('-', '_'), '', $tagName)), $this->tagNames);
    }

    public function createTag($tagName, $content = null)
    {
        $tagName = strtolower(str_replace(array('-', '_'), '', $tagName));

        if (!$this->hasTag($tagName) && !isset($this->genericTag)) {
            throw new Exception\RuntimeException('This tag name is not supported by this tag manager');
        }

        $index = array_search($tagName, $this->tagNames);

        /* @var $tag Tag */
        $tag = ($index !== false) ? $this->tags[$index] : $this->genericTag;

        $newTag = clone $tag;
        if ($content) {
            $newTag->initialize($content);
        }

        if ($newTag instanceof GenericTag) {
            $newTag->setName($tagName);
        }

        return $newTag;
    }

}
