<?php

declare (strict_types=1);
namespace Php_Parser;

interface Node_Traverser_Interface
{
    /**
     * Adds a visitor.
     *
     * @param NodeVisitor $visitor Visitor to add
     */
    public function add_visitor(Node_Visitor $visitor): void;
    /**
     * Removes an added visitor.
     */
    public function remove_visitor(Node_Visitor $visitor): void;
    /**
     * Traverses an array of nodes using the registered visitors.
     *
     * @param Node[] $nodes Array of nodes
     *
     * @return Node[] Traversed array of nodes
     */
    public function traverse(array $nodes): array;
}