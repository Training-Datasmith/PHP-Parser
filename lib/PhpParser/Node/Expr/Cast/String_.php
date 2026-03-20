<?php

declare (strict_types=1);
namespace Php_Parser\Node\Expr\Cast;

use Php_Parser\Node\Expr\Cast;
class String_ extends Cast
{
    // For use in "kind" attribute
    public const KIND_STRING = 1;
    // "string" syntax
    public const KIND_BINARY = 2;
    // "binary" syntax
    public function get_type(): string
    {
        return 'Expr_Cast_String';
    }
}