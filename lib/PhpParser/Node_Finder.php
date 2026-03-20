<?php

declare (strict_types=1);
namespace Php_Parser;

use Php_Parser\Node_Visitor\Finding_Visitor;
use Php_Parser\Node_Visitor\First_Finding_Visitor;
class Node_Finder
{
    /**
     * Find all nodes satisfying a filter callback.
     *
     * @param Node|Node[] $nodes Single node or array of nodes to search in
     * @param callable $filter Filter callback: function(Node $node) : bool
     *
     * @return Node[] Found nodes satisfying the filter callback
     */
    public function find($nodes, callable $filter): array
    {
        if ($nodes === []) {
            return [];
        }
        if (!is_array($nodes)) {
            $nodes = [$nodes];
        }
        $visitor = new Finding_Visitor($filter);
        $traverser = new Node_Traverser($visitor);
        $traverser->traverse($nodes);
        return $visitor->get_found_nodes();
    }
    /**
     * Find all nodes that are instances of a certain class.
     * @template TNode as Node
     *
     * @param Node|Node[] $nodes Single node or array of nodes to search in
     * @param class-string<TNode> $class Class name
     *
     * @return TNode[] Found nodes (all instances of $class)
     */
    public function find_instance_of($nodes, string $class): array
    {
        return $this->find($nodes, fn($node): bool => $node instanceof $class);
    }
    /**
     * Find first node satisfying a filter callback.
     *
     * @param Node|Node[] $nodes Single node or array of nodes to search in
     * @param callable $filter Filter callback: function(Node $node) : bool
     *
     * @return null|Node Found node (or null if none found)
     */
    public function find_first($nodes, callable $filter): ?Node
    {
        if ($nodes === []) {
            return null;
        }
        if (!is_array($nodes)) {
            $nodes = [$nodes];
        }
        $visitor = new First_Finding_Visitor($filter);
        $traverser = new Node_Traverser($visitor);
        $traverser->traverse($nodes);
        return $visitor->get_found_node();
    }
    /**
     * Find first node that is an instance of a certain class.
     *
     * @template TNode as Node
     *
     * @param Node|Node[] $nodes Single node or array of nodes to search in
     * @param class-string<TNode> $class Class name
     *
     * @return null|TNode Found node, which is an instance of $class (or null if none found)
     */
    public function find_first_instance_of($nodes, string $class): ?Node
    {
        return $this->find_first($nodes, fn($node): bool => $node instanceof $class);
    }
}