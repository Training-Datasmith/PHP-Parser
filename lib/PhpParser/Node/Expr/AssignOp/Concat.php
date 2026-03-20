<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr\Assign_Op;

use Php_Parser\Node\Expr\Assign_Op;
class Concat extends Assign_Op
{
    public function get_type(): string
    {
        return 'Expr_AssignOp_Concat';
    }
}