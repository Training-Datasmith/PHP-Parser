<?php

declare (strict_types=1);
namespace Php_Parser;

interface Builder
{
    /**
     * Returns the built node.
     *
     * @return Node The built node
     */
    public function get_node(): Node;
}