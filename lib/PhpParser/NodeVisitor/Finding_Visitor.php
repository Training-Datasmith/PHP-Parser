<?php

declare (strict_types=1);
namespace Php_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node_Visitor_Abstract;
/**
 * This visitor can be used to find and collect all nodes satisfying some criterion determined by
 * a filter callback.
 */
class Finding_Visitor extends Node_Visitor_Abstract
{
    /** @var callable Filter callback */
    protected $filter_callback;
    /** @var list<Node> Found nodes */
    protected array $found_nodes;
    public function __construct(callable $filter_callback)
    {
        $this->filter_callback = $filter_callback;
    }
    /**
     * Get found nodes satisfying the filter callback.
     *
     * Nodes are returned in pre-order.
     *
     * @return list<Node> Found nodes
     */
    public function get_found_nodes(): array
    {
        return $this->found_nodes;
    }
    public function before_traverse(array $nodes): ?array
    {
        $this->found_nodes = [];
        return null;
    }
    public function enter_node(Node $node)
    {
        $filter_callback = $this->filter_callback;
        if ($filter_callback($node)) {
            $this->found_nodes[] = $node;
        }
        return null;
    }
}