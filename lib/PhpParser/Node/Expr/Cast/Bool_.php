<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr\Cast;

use Php_Parser\Node\Expr\Cast;
class Bool_ extends Cast
{
    // For use in "kind" attribute
    public const KIND_BOOL = 1;
    // "bool" syntax
    public const KIND_BOOLEAN = 2;
    // "boolean" syntax
    public function get_type(): string
    {
        return 'Expr_Cast_Bool';
    }
}