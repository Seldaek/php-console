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
 * @subpackage Renderer
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Zend\View\Renderer;

use JsonSerializable,
    Traversable,
    Zend\Json\Json,
    Zend\Stdlib\IteratorToArray,
    Zend\View\Exception,
    Zend\View\Model,
    Zend\View\Renderer,
    Zend\View\Resolver;

/**
 * JSON renderer
 *
 * @category   Zend
 * @package    Zend_View
 * @subpackage Renderer
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class JsonRenderer implements Renderer, TreeRendererInterface
{
    /**
     * Whether or not to merge child models with no capture-to value set
     * @var bool
     */
    protected $mergeUnnamedChildren = false;

    /**
     * @var Resolver
     */
    protected $resolver;

    /**
     * Return the template engine object, if any
     *
     * If using a third-party template engine, such as Smarty, patTemplate,
     * phplib, etc, return the template engine object. Useful for calling
     * methods on these objects, such as for setting filters, modifiers, etc.
     *
     * @return mixed
     */
    public function getEngine()
    {
        return $this;
    }

    /**
     * Set the resolver used to map a template name to a resource the renderer may consume.
     * 
     * @todo   Determine use case for resolvers when rendering JSON
     * @param  Resolver $resolver 
     * @return Renderer
     */
    public function setResolver(Resolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Set flag indicating whether or not to merge unnamed children
     *
     * @param  bool $mergeUnnamedChildren
     * @return JsonRenderer
     */
    public function setMergeUnnamedChildren($mergeUnnamedChildren)
    {
        $this->mergeUnnamedChildren = (bool) $mergeUnnamedChildren;
        return $this;
    }
    
    /**
     * Should we merge unnamed children?
     *
     * @return bool
     */
    public function mergeUnnamedChildren()
    {
        return $this->mergeUnnamedChildren;
    }

    /**
     * Renders values as JSON
     *
     * @todo   Determine what use case exists for accepting both $nameOrModel and $values
     * @param  string|Model $name The script/resource process, or a view model
     * @param  null|array|\ArrayAccess Values to use during rendering
     * @return string The script output.
     */
    public function render($nameOrModel, $values = null)
    {
        // use case 1: View Models
        // Serialize variables in view model
        if ($nameOrModel instanceof Model) {
            if ($nameOrModel instanceof Model\JsonModel) {
                $values = $nameOrModel->serialize();
            } else {
                $values = $this->recurseModel($nameOrModel);
                $values = Json::encode($values);
            }

            return $values;
        }

        // use case 2: $nameOrModel is populated, $values is not
        // Serialize $nameOrModel
        if (null === $values) {
            if (!is_object($nameOrModel) || $nameOrModel instanceof JsonSerializable) {
                return Json::encode($nameOrModel);
            }

            if ($nameOrModel instanceof Traversable) {
                $nameOrModel = IteratorToArray::convert($nameOrModel);
                return Json::encode($nameOrModel);
            }

            return Json::encode(get_object_vars($nameOrModel));
        }

        // use case 3: Both $nameOrModel and $values are populated
        throw new Exception\DomainException(sprintf(
            '%s: Do not know how to handle operation when both $nameOrModel and $values are populated',
            __METHOD__
        ));
    }

    /**
     * Can this renderer render trees of view models?
     *
     * Yes.
     * 
     * @return true
     */
    public function canRenderTrees()
    {
        return true;
    }

    /**
     * Retrieve values from a model and recurse its children to build a data structure
     * 
     * @param  Model $model 
     * @return array
     */
    protected function recurseModel(Model $model)
    {
        $values = $model->getVariables();
        if (!$model->hasChildren()) {
            return $values;
        }

        $mergeChildren = $this->mergeUnnamedChildren();
        foreach ($model as $child) {
            $captureTo = $child->captureTo();
            if (!$captureTo && !$mergeChildren) {
                // We don't want to do anything with this child
                continue;
            }

            $childValues = $this->recurseModel($child);
            if ($captureTo) {
                // Capturing to a specific key
                $values[$captureTo] = $childValues;
            } elseif ($mergeChildren) {
                // Merging values with parent
                $values = array_replace_recursive($values, $childValues);
            }
        }
        return $values;
    }
}
