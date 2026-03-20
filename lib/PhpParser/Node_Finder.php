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
     * Performs a depth-first traversal of the AST rooted at $nodes and
     * returns every node for which $filter returns true.
     *
     * @param Node|Node[] $nodes  Single node or array of nodes to search in
     * @param callable    $filter Predicate: function(Node $node): bool — return true to include the node
     *
     * @return Node[] Found nodes satisfying the filter callback, in traversal order
     *
     * @complexity O(n) where n is the total number of nodes in the subtree
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
     *
     * Shorthand for {@see find()} with an instanceof filter. Generic typing
     * lets PHPStan and Psalm infer the concrete node type in the return array.
     *
     * @template TNode of Node
     *
     * @param Node|Node[]         $nodes Single node or array of nodes to search in
     * @param class-string<TNode> $class Fully-qualified class name to match against
     *
     * @return TNode[] Found nodes, all guaranteed to be instances of $class
     *
     * @complexity O(n) where n is the total number of nodes in the subtree
     */
    public function find_instance_of($nodes, string $class): array
    {
        return $this->find($nodes, fn($node): bool => $node instanceof $class);
    }
    /**
     * Find first node satisfying a filter callback.
     *
     * Traverses depth-first and returns the first node for which $filter
     * returns true, then stops. More efficient than {@see find()} when only
     * one match is expected.
     *
     * @param Node|Node[] $nodes  Single node or array of nodes to search in
     * @param callable    $filter Predicate: function(Node $node): bool
     *
     * @return Node|null The first matching node, or null if no match is found
     *
     * @complexity O(n) worst case, O(1) best case (match at root)
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
     * Shorthand for {@see find_first()} with an instanceof filter.
     * Traversal stops as soon as the first match is found.
     *
     * @template TNode of Node
     *
     * @param Node|Node[]         $nodes Single node or array of nodes to search in
     * @param class-string<TNode> $class Fully-qualified class name to match against
     *
     * @return TNode|null The first node that is an instance of $class, or null if none found
     *
     * @complexity O(n) worst case, O(1) best case (match at root)
     */
    public function find_first_instance_of($nodes, string $class): ?Node
    {
        return $this->find_first($nodes, fn($node): bool => $node instanceof $class);
    }
}