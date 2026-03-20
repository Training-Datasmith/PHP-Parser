<?php

declare (strict_types=1);
namespace Php_Parser\Node_Visitor;

use function array_pop;
use function count;
use Php_Parser\Node;
use Php_Parser\Node_Visitor_Abstract;
/**
 * Visitor that connects a child node to its parent node.
 *
 * With <code>$weakReferences=false</code> on the child node, the parent node can be accessed through
 * <code>$node->getAttribute('parent')</code>.
 *
 * With <code>$weakReferences=true</code> the attribute name is "weak_parent" instead.
 */
final class Parent_Connecting_Visitor extends Node_Visitor_Abstract
{
    /**
     * @var Node[]
     */
    private array $stack = [];
    private bool $weak_references;
    public function __construct(bool $weak_references = false)
    {
        $this->weak_references = $weak_references;
    }
    public function before_traverse(array $nodes): void
    {
        $this->stack = [];
    }
    public function enter_node(Node $node): void
    {
        if (!empty($this->stack)) {
            $parent = $this->stack[count($this->stack) - 1];
            if ($this->weak_references) {
                $node->set_attribute('weak_parent', \WeakReference::create($parent));
            } else {
                $node->set_attribute('parent', $parent);
            }
        }
        $this->stack[] = $node;
    }
    public function leave_node(Node $node): void
    {
        array_pop($this->stack);
    }
}