<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr\Cast;

use Php_Parser\Node\Expr\Cast;
class Int_ extends Cast
{
    // For use in "kind" attribute
    public const KIND_INT = 1;
    // "int" syntax
    public const KIND_INTEGER = 2;
    // "integer" syntax
    public function get_type(): string
    {
        return 'Expr_Cast_Int';
    }
}