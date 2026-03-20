<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr\Binary_Op;

use Php_Parser\Node\Expr\Binary_Op;
class Logical_Xor extends Binary_Op
{
    public function get_operator_sigil(): string
    {
        return 'xor';
    }
    public function get_type(): string
    {
        return 'Expr_BinaryOp_LogicalXor';
    }
}