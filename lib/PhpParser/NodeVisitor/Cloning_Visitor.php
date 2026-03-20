<?php

declare (strict_types=1);
namespace Php_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node_Visitor_Abstract;
/**
 * Visitor cloning all nodes and linking to the original nodes using an attribute.
 *
 * This visitor is required to perform format-preserving pretty prints.
 */
class Cloning_Visitor extends Node_Visitor_Abstract
{
    public function enter_node(Node $orig_node)
    {
        $node = clone $orig_node;
        $node->set_attribute('origNode', $orig_node);
        return $node;
    }
}