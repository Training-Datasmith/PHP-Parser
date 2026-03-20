<?php

declare (strict_types=1);
namespace Php_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node_Visitor;
use Php_Parser\Node_Visitor_Abstract;
/**
 * This visitor can be used to find the first node satisfying some criterion determined by
 * a filter callback.
 */
class First_Finding_Visitor extends Node_Visitor_Abstract
{
    /** @var callable Filter callback */
    protected $filter_callback;
    /** @var null|Node Found node */
    protected ?Node $found_node = null;
    public function __construct(callable $filter_callback)
    {
        $this->filter_callback = $filter_callback;
    }
    /**
     * Get found node satisfying the filter callback.
     *
     * Returns null if no node satisfies the filter callback.
     *
     * @return null|Node Found node (or null if not found)
     */
    public function get_found_node(): ?Node
    {
        return $this->found_node;
    }
    public function before_traverse(array $nodes): ?array
    {
        $this->found_node = null;
        return null;
    }
    public function enter_node(Node $node)
    {
        $filter_callback = $this->filter_callback;
        if ($filter_callback($node)) {
            $this->found_node = $node;
            return Node_Visitor::STOP_TRAVERSAL;
        }
        return null;
    }
}