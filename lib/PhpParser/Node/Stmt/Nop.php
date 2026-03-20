<?php

declare (strict_types=1);
namespace Php_Parser\Node\Stmt;

use Php_Parser\Node;
/** Nop/empty statement (;). */
class Nop extends Node\Stmt
{
    public function get_sub_node_names(): array
    {
        return [];
    }
    public function get_type(): string
    {
        return 'Stmt_Nop';
    }
}