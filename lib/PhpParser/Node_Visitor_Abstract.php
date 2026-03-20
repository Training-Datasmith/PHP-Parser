<?php

declare (strict_types=1);
namespace Php_Parser;

/**
 * @codeCoverageIgnore
 */
abstract class Node_Visitor_Abstract implements Node_Visitor
{
    public function before_traverse(array $nodes)
    {
        return null;
    }
    public function enter_node(Node $node)
    {
        return null;
    }
    public function leave_node(Node $node)
    {
        return null;
    }
    public function after_traverse(array $nodes)
    {
        return null;
    }
}