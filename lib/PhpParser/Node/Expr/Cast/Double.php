<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr\Cast;

use Php_Parser\Node\Expr\Cast;
class Double extends Cast
{
    // For use in "kind" attribute
    public const KIND_DOUBLE = 1;
    // "double" syntax
    public const KIND_FLOAT = 2;
    // "float" syntax
    public const KIND_REAL = 3;
    // "real" syntax
    public function get_type(): string
    {
        return 'Expr_Cast_Double';
    }
}