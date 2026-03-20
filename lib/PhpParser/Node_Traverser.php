<?php

declare (strict_types=1);
namespace Php_Parser;

class Node_Traverser implements Node_Traverser_Interface
{
    /**
     * @deprecated Use NodeVisitor::DONT_TRAVERSE_CHILDREN instead.
     */
    public const DONT_TRAVERSE_CHILDREN = Node_Visitor::DONT_TRAVERSE_CHILDREN;
    /**
     * @deprecated Use NodeVisitor::STOP_TRAVERSAL instead.
     */
    public const STOP_TRAVERSAL = Node_Visitor::STOP_TRAVERSAL;
    /**
     * @deprecated Use NodeVisitor::REMOVE_NODE instead.
     */
    public const REMOVE_NODE = Node_Visitor::REMOVE_NODE;
    /**
     * @deprecated Use NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN instead.
     */
    public const DONT_TRAVERSE_CURRENT_AND_CHILDREN = Node_Visitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
    /** @var list<NodeVisitor> Visitors */
    protected array $visitors = [];
    /** @var bool Whether traversal should be stopped */
    protected bool $stop_traversal;
    /**
     * Creates a traverser pre-loaded with the given visitors.
     *
     * Visitors are applied in the order they are passed for enterNode() calls,
     * and in reverse order for leaveNode() calls, mirroring a call stack.
     *
     * @param Node_Visitor ...$visitors One or more node visitors to register
     *
     * @since 1.0
     */
    public function __construct(Node_Visitor ...$visitors)
    {
        $this->visitors = $visitors;
    }
    /**
     * Adds a visitor to the end of the visitor chain.
     *
     * The new visitor will participate in the next call to {@see traverse()}.
     * Adding a visitor after traversal has started has no effect on the
     * current traversal pass.
     *
     * @param Node_Visitor $visitor Visitor to append to the chain
     */
    public function add_visitor(Node_Visitor $visitor): void
    {
        $this->visitors[] = $visitor;
    }
    /**
     * Removes a previously registered visitor from the chain.
     *
     * Comparison is done by identity (===). If the visitor is not registered,
     * the call is a no-op.
     *
     * @param Node_Visitor $visitor The visitor instance to remove
     */
    public function remove_visitor(Node_Visitor $visitor): void
    {
        $index = array_search($visitor, $this->visitors);
        if ($index !== false) {
            array_splice($this->visitors, $index, 1, []);
        }
    }
    /**
     * Traverses an array of top-level nodes using all registered visitors.
     *
     * Performs a depth-first traversal, calling visitors in registration order
     * on enterNode() and in reverse order on leaveNode(). Visitors can control
     * traversal via the sentinel return values defined on {@see NodeVisitor}.
     *
     * The traversal order is:
     *  1. beforeTraverse($nodes) on all visitors (in order)
     *  2. Depth-first descent: enterNode → children → leaveNode
     *  3. afterTraverse($nodes) on all visitors (in reverse order)
     *
     * @param Node[] $nodes Top-level statement nodes to traverse (e.g. from Parser::parse())
     *
     * @return Node[] Resulting node array after all visitor transformations
     *
     * @complexity O(n * v) where n is the number of nodes and v is the number of visitors
     *
     * @see NodeVisitor for return value constants that control traversal
     */
    public function traverse(array $nodes): array
    {
        $this->stop_traversal = false;
        foreach ($this->visitors as $visitor) {
            if (null !== $return = $visitor->before_traverse($nodes)) {
                $nodes = $return;
            }
        }
        $nodes = $this->traverse_array($nodes);
        for ($i = \count($this->visitors) - 1; $i >= 0; --$i) {
            $visitor = $this->visitors[$i];
            if (null !== $return = $visitor->after_traverse($nodes)) {
                $nodes = $return;
            }
        }
        return $nodes;
    }
    /**
     * Recursively traverse a node.
     *
     * @param Node $node Node to traverse.
     */
    protected function traverse_node(Node $node): void
    {
        foreach ($node->get_sub_node_names() as $name) {
            $sub_node = $node->{$name};
            if (\is_array($sub_node)) {
                $node->{$name} = $this->traverse_array($sub_node);
                if ($this->stop_traversal) {
                    break;
                }
                continue;
            }
            if (!$sub_node instanceof Node) {
                continue;
            }
            $traverse_children = true;
            $visitor_index = -1;
            foreach ($this->visitors as $visitor_index => $visitor) {
                $return = $visitor->enter_node($sub_node);
                if (null !== $return) {
                    if ($return instanceof Node) {
                        $this->ensure_replacement_reasonable($sub_node, $return);
                        $sub_node = $node->{$name} = $return;
                    } elseif (Node_Visitor::DONT_TRAVERSE_CHILDREN === $return) {
                        $traverse_children = false;
                    } elseif (Node_Visitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN === $return) {
                        $traverse_children = false;
                        break;
                    } elseif (Node_Visitor::STOP_TRAVERSAL === $return) {
                        $this->stop_traversal = true;
                        break 2;
                    } elseif (Node_Visitor::REPLACE_WITH_NULL === $return) {
                        $node->{$name} = null;
                        continue 2;
                    } else {
                        throw new \LogicException('enterNode() returned invalid value of type ' . gettype($return));
                    }
                }
            }
            if ($traverse_children) {
                $this->traverse_node($sub_node);
                if ($this->stop_traversal) {
                    break;
                }
            }
            for (; $visitor_index >= 0; --$visitor_index) {
                $visitor = $this->visitors[$visitor_index];
                $return = $visitor->leave_node($sub_node);
                if (null !== $return) {
                    if ($return instanceof Node) {
                        $this->ensure_replacement_reasonable($sub_node, $return);
                        $sub_node = $node->{$name} = $return;
                    } elseif (Node_Visitor::STOP_TRAVERSAL === $return) {
                        $this->stop_traversal = true;
                        break 2;
                    } elseif (Node_Visitor::REPLACE_WITH_NULL === $return) {
                        $node->{$name} = null;
                        break;
                    } elseif (\is_array($return)) {
                        throw new \LogicException('leaveNode() may only return an array ' . 'if the parent structure is an array');
                    } else {
                        throw new \LogicException('leaveNode() returned invalid value of type ' . gettype($return));
                    }
                }
            }
        }
    }
    /**
     * Recursively traverse array (usually of nodes).
     *
     * @param Node[] $nodes Array to traverse
     *
     * @return Node[] Result of traversal (may be original array or changed one)
     */
    protected function traverse_array(array $nodes): array
    {
        $do_nodes = [];
        foreach ($nodes as $i => $node) {
            if (!$node instanceof Node) {
                if (\is_array($node)) {
                    throw new \LogicException('Invalid node structure: Contains nested arrays');
                }
                continue;
            }
            $traverse_children = true;
            $visitor_index = -1;
            foreach ($this->visitors as $visitor_index => $visitor) {
                $return = $visitor->enter_node($node);
                if (null !== $return) {
                    if ($return instanceof Node) {
                        $this->ensure_replacement_reasonable($node, $return);
                        $nodes[$i] = $node = $return;
                    } elseif (\is_array($return)) {
                        $do_nodes[] = [$i, $return];
                        continue 2;
                    } elseif (Node_Visitor::REMOVE_NODE === $return) {
                        $do_nodes[] = [$i, []];
                        continue 2;
                    } elseif (Node_Visitor::DONT_TRAVERSE_CHILDREN === $return) {
                        $traverse_children = false;
                    } elseif (Node_Visitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN === $return) {
                        $traverse_children = false;
                        break;
                    } elseif (Node_Visitor::STOP_TRAVERSAL === $return) {
                        $this->stop_traversal = true;
                        break 2;
                    } elseif (Node_Visitor::REPLACE_WITH_NULL === $return) {
                        throw new \LogicException('REPLACE_WITH_NULL can not be used if the parent structure is an array');
                    } else {
                        throw new \LogicException('enterNode() returned invalid value of type ' . gettype($return));
                    }
                }
            }
            if ($traverse_children) {
                $this->traverse_node($node);
                if ($this->stop_traversal) {
                    break;
                }
            }
            for (; $visitor_index >= 0; --$visitor_index) {
                $visitor = $this->visitors[$visitor_index];
                $return = $visitor->leave_node($node);
                if (null !== $return) {
                    if ($return instanceof Node) {
                        $this->ensure_replacement_reasonable($node, $return);
                        $nodes[$i] = $node = $return;
                    } elseif (\is_array($return)) {
                        $do_nodes[] = [$i, $return];
                        break;
                    } elseif (Node_Visitor::REMOVE_NODE === $return) {
                        $do_nodes[] = [$i, []];
                        break;
                    } elseif (Node_Visitor::STOP_TRAVERSAL === $return) {
                        $this->stop_traversal = true;
                        break 2;
                    } elseif (Node_Visitor::REPLACE_WITH_NULL === $return) {
                        throw new \LogicException('REPLACE_WITH_NULL can not be used if the parent structure is an array');
                    } else {
                        throw new \LogicException('leaveNode() returned invalid value of type ' . gettype($return));
                    }
                }
            }
        }
        if (!empty($do_nodes)) {
            while ([$i, $replace] = array_pop($do_nodes)) {
                array_splice($nodes, $i, 1, $replace);
            }
        }
        return $nodes;
    }
    private function ensure_replacement_reasonable(Node $old, Node $new): void
    {
        if ($old instanceof Node\Stmt && $new instanceof Node\Expr) {
            throw new \LogicException("Trying to replace statement ({$old->get_type()}) " . "with expression ({$new->get_type()}). Are you missing a " . 'Stmt_Expression wrapper?');
        }
        if ($old instanceof Node\Expr && $new instanceof Node\Stmt) {
            throw new \LogicException("Trying to replace expression ({$old->get_type()}) " . "with statement ({$new->get_type()})");
        }
    }
}