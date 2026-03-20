<?php

declare (strict_types=1);
namespace Php_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node_Visitor_Abstract;
/**
 * Visitor that connects a child node to its parent node
 * as well as its sibling nodes.
 *
 * With <code>$weakReferences=false</code> on the child node, the parent node can be accessed through
 * <code>$node->getAttribute('parent')</code>, the previous
 * node can be accessed through <code>$node->getAttribute('previous')</code>,
 * and the next node can be accessed through <code>$node->getAttribute('next')</code>.
 *
 * With <code>$weakReferences=true</code> attribute names are prefixed by "weak_", e.g. "weak_parent".
 */
final class Node_Connecting_Visitor extends Node_Visitor_Abstract
{
    /**
     * @var Node[]
     */
    private array $stack = [];
    private ?\Php_Parser\Node $previous = null;
    private bool $weak_references;
    public function __construct(bool $weak_references = false)
    {
        $this->weak_references = $weak_references;
    }
    public function before_traverse(array $nodes): void
    {
        $this->stack = [];
        $this->previous = null;
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
        if ($this->previous !== null) {
            if ($this->weak_references) {
                if ($this->previous->get_attribute('weak_parent') === $node->get_attribute('weak_parent')) {
                    $node->set_attribute('weak_previous', \WeakReference::create($this->previous));
                    $this->previous->set_attribute('weak_next', \WeakReference::create($node));
                }
            } elseif ($this->previous->get_attribute('parent') === $node->get_attribute('parent')) {
                $node->set_attribute('previous', $this->previous);
                $this->previous->set_attribute('next', $node);
            }
        }
        $this->stack[] = $node;
    }
    public function leave_node(Node $node): void
    {
        $this->previous = $node;
        array_pop($this->stack);
    }
}