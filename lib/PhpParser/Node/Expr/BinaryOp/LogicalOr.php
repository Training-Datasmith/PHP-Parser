<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr\Binary_Op;

use Php_Parser\Node\Expr\Binary_Op;
class Logical_Or extends Binary_Op
{
    public function get_operator_sigil(): string
    {
        return 'or';
    }
    public function get_type(): string
    {
        return 'Expr_BinaryOp_LogicalOr';
    }
}